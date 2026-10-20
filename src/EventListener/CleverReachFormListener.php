<?php

declare(strict_types=1);

namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reicht die Anmeldung eines Contao-Formulars serverseitig an CleverReach weiter.
 *
 * Das Formular ist ein ganz normales Contao-Formular und wird auch so gestylt; der
 * Browser spricht also ausschliesslich mit steelers.de. Die Flow-URL aus dem von
 * CleverReach gelieferten Formular (dessen "action"-Attribut) steht am Formular selbst
 * (tl_form.cleverreachUrl). Ist sie leer, tut der Listener nichts – ein weiteres
 * CleverReach-Formular braucht deshalb keinen Code, nur einen Backend-Eintrag.
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
 * Feldnamen: CleverReach erwartet "email" und "tags" unveraendert, alle uebrigen
 * Kontaktattribute mit dem Praefix "global." (z. B. "global.firstname"). Der
 * Contao-Feldname ist deshalb der Attributname ohne Praefix – aus dem Feld "firstname"
 * wird "global.firstname". Der Punkt kann nicht direkt im Contao-Feldnamen stehen, weil
 * PHP Punkte in POST-Namen zu Unterstrichen macht.
 *
 * Die Double-Opt-In-Mail verschickt CleverReach selbst (daher die Flow-URL mit
 * "/confirm"); die Antwort von CleverReach sieht der Besucher nie.
 */
#[AsHook('prepareFormData')]
class CleverReachFormListener
{
    /**
     * Feldnamen, die CleverReach ohne "global."-Praefix erwartet.
     */
    private const PLAIN_FIELDS = ['email', 'tags'];

    /**
     * Honeypot aus dem CleverReach-Formular ("cr-nope"). Wird mitgeschickt und bleibt
     * leer – gefuellt wertet CleverReach die Anmeldung als Spam.
     */
    private const HONEYPOT_FIELD = 'email_confirm';

    /**
     * Kurz halten: der Besucher wartet auf die Antwort des Formulars.
     */
    private const TIMEOUT = 10.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
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
        // Liest tl_form (Contao\Hybrid::__get); fehlt die Spalte noch, greift "??".
        $url = trim((string) ($objForm->cleverreachUrl ?? ''));

        if ('' === $url) {
            return;
        }

        $title = (string) ($objForm->title ?? '');

        try {
            $response = $this->httpClient->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
                'body' => $this->buildBody($arrSubmitted),
                'timeout' => self::TIMEOUT,
                // Die Flow-URL antwortet mit einer Weiterleitung auf die
                // CleverReach-Hinweisseite. Die interessiert hier nicht, und ihr zu
                // folgen wuerde den Besucher nur warten lassen.
                'max_redirects' => 0,
            ]);

            // Loest die Anfrage ueberhaupt erst aus (der HttpClient arbeitet lazy).
            $status = $response->getStatusCode();

            if ($status >= 400) {
                $this->logger->error(\sprintf(
                    'CleverReach hat die Anmeldung aus Formular "%s" abgelehnt (HTTP %d): %s',
                    $title,
                    $status,
                    substr($response->getContent(false), 0, 500),
                ));

                $this->fail($objForm);

                return;
            }

            // Eine Ablehnung koennte auch mit HTTP 200 und einem Hinweis im Body
            // zurueckkommen – die faellt hier durchs Raster. Weil der Besucher die
            // Antwort nie sieht, ist das Log der einzige Ort, an dem sich das
            // nachvollziehen laesst, deshalb immer eine Zeile.
            $this->logger->info(\sprintf(
                'Anmeldung aus Formular "%s" an CleverReach uebergeben (HTTP %d).',
                $title,
                $status,
            ));
        } catch (HttpClientExceptionInterface $e) {
            $this->logger->error(\sprintf(
                'Die Anmeldung aus Formular "%s" konnte nicht an CleverReach uebergeben werden: %s',
                $title,
                $e->getMessage(),
            ));

            $this->fail($objForm);
        }
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

    /**
     * Baut den POST-Body genau so, wie ihn der Browser aus dem CleverReach-Formular
     * geschickt haette: Mehrfachwerte (z. B. Checkboxen) als "name[]", Punkte im
     * Attributnamen unkodiert.
     *
     * @param array<string, mixed> $arrSubmitted
     */
    private function buildBody(array $arrSubmitted): string
    {
        $pairs = [[self::HONEYPOT_FIELD, '']];

        foreach ($arrSubmitted as $name => $value) {
            $name = (string) $name;
            $target = \in_array($name, self::PLAIN_FIELDS, true) ? $name : 'global.'.$name;

            if (\is_array($value)) {
                foreach ($value as $item) {
                    $pairs[] = [$target.'[]', (string) $item];
                }

                continue;
            }

            $pairs[] = [$target, (string) $value];
        }

        return implode('&', array_map(
            // rawurlencode() laesst "." und "-" stehen und kodiert "[]" – dasselbe
            // Ergebnis wie die Formularkodierung des Browsers.
            static fn (array $pair): string => rawurlencode($pair[0]).'='.rawurlencode($pair[1]),
            $pairs,
        ));
    }
}
