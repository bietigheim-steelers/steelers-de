<?php

declare(strict_types=1);

namespace App\EventListener;

use CleverReach\SDK\CleverReachClient;
use CleverReach\SDK\Exception\CleverReachException;
use CleverReach\SDK\Exception\ValidationException;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Traegt die Anmeldung eines Contao-Formulars ueber die CleverReach-REST-API ein.
 *
 * Das Formular ist ein ganz normales Contao-Formular und wird auch so gestylt; der
 * Browser spricht also ausschliesslich mit steelers.de. Welche Empfaengergruppe und
 * welches CleverReach-Formular (fuer die Double-Opt-In-Mail) gemeint sind, steht am
 * Formular selbst (tl_form.cleverreachGroupId / cleverreachFormId). Ist eines von
 * beiden leer, tut der Listener nichts – ein weiteres Newsletter-Formular braucht
 * deshalb keinen Code, nur einen Backend-Eintrag.
 *
 * Ablauf:
 *  1. Empfaenger in der Gruppe anlegen, aber NICHT aktivieren ("activated" fehlt).
 *  2. Ueber das CleverReach-Formular die Bestaetigungsmail ausloesen. Aktiv wird der
 *     Empfaenger erst, wenn er den Link darin anklickt.
 *
 * Bewusst "prepareFormData" und nicht "processFormData": dieser Hook laeuft nach der
 * Validierung, aber noch vor Session, E-Mail-Versand, "Eingaben speichern" und
 * Weiterleitung – und Contao prueft direkt dahinter Form::hasErrors() und bricht ab
 * (Form::processFormData(), "see #6611"). Scheitert die Uebergabe, macht
 * Form::addError() daraus also einen echten Formularfehler: der Besucher bleibt mit
 * seinen Eingaben auf dem Formular stehen, es wird nichts gespeichert, keine Mail
 * verschickt und nicht auf die Bestaetigungsseite weitergeleitet. "processFormData"
 * koennte das nicht – dort sind Mail und Speicherung schon durch.
 *
 * Feldnamen: "email" ist die Adresse, "tags" nimmt CleverReach-Tags auf, alle uebrigen
 * Felder werden unter ihrem Namen als globales Attribut (global_attributes) angelegt –
 * das Feld "firstname" fuellt also das CleverReach-Attribut "firstname". Das Attribut
 * muss in CleverReach existieren.
 */
#[AsHook('prepareFormData')]
class CleverReachFormListener
{
    /**
     * Formularfelder, die kein Attribut sind.
     */
    private const EMAIL_FIELD = 'email';
    private const TAGS_FIELD = 'tags';

    /**
     * Wert, den eine Checkbox ohne eigenen Optionswert abschickt. Das ist die
     * Einwilligung und kein Tag; der Nachweis dafuer steht in den doidata.
     */
    private const CHECKBOX_VALUE = 'on';

    private const SOURCE = 'steelers.de';

    /**
     * Versuche bei Transportfehlern (DNS, Verbindungsaufbau, Timeout). Ein einzelner
     * Aussetzer soll nicht die ganze Anmeldung scheitern lassen. HTTP-Antworten (auch
     * 4xx/5xx) werden nicht wiederholt. Der Timeout selbst steht in config/services.yml.
     */
    private const ATTEMPTS = 2;

    /**
     * Pause zwischen zwei Versuchen in Mikrosekunden.
     */
    private const RETRY_DELAY = 400000;

    public function __construct(
        private readonly CleverReachClient $client,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed>      $arrSubmitted
     * @param array<string, mixed>      $arrLabels
     * @param array<string, mixed>      $arrFields
     * @param array<string, mixed>|null $arrFiles
     */
    public function __invoke(array $arrSubmitted, array $arrLabels, array $arrFields, Form $objForm, array|null $arrFiles = null): void
    {
        // Liest tl_form (Contao\Hybrid::__get); fehlen die Spalten noch, greift "??".
        $groupId = trim((string) ($objForm->cleverreachGroupId ?? ''));
        $formId = trim((string) ($objForm->cleverreachFormId ?? ''));

        if ('' === $groupId || '' === $formId) {
            return;
        }

        $title = (string) ($objForm->title ?? '');
        $email = trim((string) ($arrSubmitted[self::EMAIL_FIELD] ?? ''));

        if ('' === $email) {
            $this->logger->error(\sprintf(
                'Formular "%s" liefert keine E-Mail-Adresse (Feld "%s") fuer CleverReach.',
                $title,
                self::EMAIL_FIELD,
            ));

            $this->fail($objForm);

            return;
        }

        try {
            $this->subscribe($groupId, $formId, $email, $arrSubmitted);

            // Der Besucher sieht von CleverReach nichts. Damit sich spaeter nachvollziehen
            // laesst, ob eine Anmeldung dort angekommen ist, wird jede Uebergabe
            // protokolliert und nicht nur die gescheiterten.
            $this->logger->info(\sprintf(
                'Anmeldung aus Formular "%s" an CleverReach uebergeben (Gruppe %s, Formular %s).',
                $title,
                $groupId,
                $formId,
            ));
        } catch (CleverReachException $e) {
            $this->logger->error(\sprintf(
                'Die Anmeldung aus Formular "%s" konnte nicht an CleverReach uebergeben werden (HTTP %s): %s %s',
                $title,
                $e->statusCode() ?? '-',
                $e->getMessage(),
                substr((string) $e->responseBody(), 0, 500),
            ));

            $this->fail($objForm);
        }
    }

    /**
     * @param array<string, mixed> $submitted
     *
     * @throws CleverReachException
     */
    private function subscribe(string $groupId, string $formId, string $email, array $submitted): void
    {
        $duplicate = false;

        try {
            $this->call(fn (): array => $this->client->request(
                'POST',
                \sprintf('groups/%s/receivers', rawurlencode($groupId)),
                [],
                $this->buildReceiver($email, $submitted),
            ));
        } catch (ValidationException $e) {
            // Gibt es die Adresse in der Gruppe schon, lehnt CleverReach das Anlegen ab.
            // Dann reicht es, die Bestaetigungsmail (erneut) auszuloesen – bei einem
            // Empfaenger, der noch auf die Bestaetigung wartet, ist genau das gewollt.
            if (1 !== preg_match('/duplicate|already|exist/i', $e->getMessage().' '.$e->responseBody())) {
                throw $e;
            }

            $duplicate = true;

            $this->logger->info('CleverReach kennt den Empfaenger in dieser Gruppe bereits, loese nur die Bestaetigungsmail aus.');
        }

        try {
            $this->call(fn (): array => $this->client->request(
                'POST',
                \sprintf('forms/%s/send/activate', rawurlencode($formId)),
                [],
                ['email' => $email, 'doidata' => $this->buildDoiData()],
            ));
        } catch (ValidationException $e) {
            // Bereits aktive Empfaenger bekommen keine Bestaetigungsmail. Fuer den
            // Besucher ist das kein Fehler – und er soll auch nicht erfahren, ob die
            // Adresse schon im Verteiler steht.
            if (!$duplicate) {
                throw $e;
            }

            $this->logger->info(\sprintf('CleverReach hat keine Bestaetigungsmail fuer einen bestehenden Empfaenger verschickt: %s', $e->getMessage()));
        }
    }

    /**
     * Fuehrt einen API-Aufruf aus und wiederholt ihn bei Transportfehlern.
     *
     * Das SDK erwartet als Antwort ein JSON-Objekt oder -Array. "send/activate" antwortet
     * aber mit einem nackten Boolean; das SDK wirft dafuer eine CleverReachException ganz
     * ohne Status und ohne vorherige Exception. Dahinter steckt eine erfolgreiche Antwort
     * (Status < 400), deshalb zaehlt das hier als Erfolg.
     *
     * @param callable(): array<mixed> $request
     *
     * @return array<mixed>
     *
     * @throws CleverReachException
     */
    private function call(callable $request): array
    {
        for ($attempt = 1; ; ++$attempt) {
            try {
                return $request();
            } catch (CleverReachException $e) {
                if (null === $e->statusCode() && null === $e->getPrevious()) {
                    return [];
                }

                if (!$e->getPrevious() instanceof ClientExceptionInterface || $attempt >= self::ATTEMPTS) {
                    throw $e;
                }

                $this->logger->warning(\sprintf(
                    'CleverReach-Uebergabe fehlgeschlagen (Versuch %d von %d): %s',
                    $attempt,
                    self::ATTEMPTS,
                    $e->getPrevious()->getMessage(),
                ));

                usleep(self::RETRY_DELAY);
            }
        }
    }

    /**
     * Der Empfaenger wird ohne "activated" angelegt, damit er bis zur Bestaetigung
     * inaktiv bleibt. Fehlen "registered" und "activated" beide, aktiviert CleverReach
     * den Empfaenger sofort – und die Bestaetigungsmail waere wirkungslos.
     *
     * @param array<string, mixed> $submitted
     *
     * @return array<string, mixed>
     */
    private function buildReceiver(string $email, array $submitted): array
    {
        $attributes = [];

        foreach ($submitted as $name => $value) {
            $name = (string) $name;

            if (self::EMAIL_FIELD === $name || self::TAGS_FIELD === $name) {
                continue;
            }

            $value = $this->stringify($value);

            if ('' !== $value) {
                $attributes[$name] = $value;
            }
        }

        $tags = array_values(array_filter(
            array_map('trim', array_map('strval', (array) ($submitted[self::TAGS_FIELD] ?? []))),
            static fn (string $tag): bool => '' !== $tag && self::CHECKBOX_VALUE !== $tag,
        ));

        $receiver = [
            'email' => $email,
            'registered' => time(),
            'source' => self::SOURCE,
        ];

        if ([] !== $attributes) {
            $receiver['global_attributes'] = $attributes;
        }

        if ([] !== $tags) {
            $receiver['tags'] = $tags;
        }

        return $receiver;
    }

    /**
     * Nachweis fuer das Double-Opt-In: Herkunft der Anmeldung.
     *
     * @return array{user_ip: string, referer: string, user_agent: string}
     */
    private function buildDoiData(): array
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return ['user_ip' => '', 'referer' => '', 'user_agent' => ''];
        }

        return [
            'user_ip' => (string) $request->getClientIp(),
            // Ohne Query-String: die Seite genuegt als Nachweis.
            'referer' => $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo(),
            'user_agent' => (string) $request->headers->get('User-Agent', ''),
        ];
    }

    private function stringify(mixed $value): string
    {
        if (\is_array($value)) {
            return implode(', ', array_filter(array_map('strval', $value), static fn (string $v): bool => '' !== $v));
        }

        return trim((string) $value);
    }

    /**
     * Macht aus der gescheiterten Uebergabe einen Formularfehler. Contao bricht die
     * Verarbeitung dahinter ab und rendert das Formular mit den Eingaben erneut; die
     * Meldung gibt das Wrapper-Template ueber "errors" aus.
     */
    private function fail(Form $objForm): void
    {
        $objForm->addError(
            $GLOBALS['TL_LANG']['ERR']['cleverreach']
            ?? 'Die Anmeldung konnte gerade nicht abgeschlossen werden. Bitte versuche es in einigen Minuten noch einmal.'
        );
    }
}
