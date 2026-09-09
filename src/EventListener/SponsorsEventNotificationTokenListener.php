<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Sponsors\SponsorsEventResolver;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Terminal42\NotificationCenterBundle\Event\CreateParcelEvent;
use Terminal42\NotificationCenterBundle\Event\GetTokenDefinitionsForNotificationTypeEvent;
use Terminal42\NotificationCenterBundle\NotificationType\FormGeneratorNotificationType;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\FormConfigStamp;
use Terminal42\NotificationCenterBundle\Parcel\Stamp\TokenCollectionStamp;
use Terminal42\NotificationCenterBundle\Token\Definition\Factory\TokenDefinitionFactoryInterface;
use Terminal42\NotificationCenterBundle\Token\Definition\HtmlTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TextTokenDefinition;
use Terminal42\NotificationCenterBundle\Token\Definition\TokenDefinitionInterface;

/**
 * Stellt die Daten des Sponsor-Events als Tokens für die Formular-Benachrichtigung bereit.
 *
 * Mehrere Sponsor-Events teilen sich ein Formular und damit auch eine Benachrichtigung
 * im Notification Center. Betreff und Text stehen deshalb nicht in der Nachricht selbst,
 * sondern am Event (tl_sponsors_event.notificationSubject/notificationText); in der
 * Nachricht stehen nur die Platzhalter:
 *
 *   Betreff:   ##sponsor_event_subject##
 *   Textmail:  ##sponsor_event_text##
 *   HTML-Mail: ##sponsor_event_text_html##
 *
 * Eingegeben wird nur der Rohtext; für die HTML-Mail werden Sonderzeichen maskiert und
 * Zeilenumbrüche zu <br> (##sponsor_event_text_html##).
 *
 * Welches Event gemeint ist, steht im URL-Parameter "token" der Anfrage, mit der das
 * Formular abgeschickt wurde (siehe App\Sponsors\SponsorsEventResolver). Die Tokens
 * werden beim Erzeugen des Parcels gesetzt und damit auch bei asynchronem Versand
 * mitserialisiert.
 */
class SponsorsEventNotificationTokenListener
{
    /**
     * Token-Name => Definitionsklasse. Die Klasse entscheidet, in welchen Feldern des
     * Notification Centers das Token vorgeschlagen wird (HTML nur im HTML-Feld).
     *
     * @var array<string, class-string<TokenDefinitionInterface>>
     */
    private const TOKENS = array(
        'sponsor_event_title' => TextTokenDefinition::class,
        'sponsor_event_date' => TextTokenDefinition::class,
        'sponsor_event_time' => TextTokenDefinition::class,
        'sponsor_event_subject' => TextTokenDefinition::class,
        'sponsor_event_text' => TextTokenDefinition::class,
        'sponsor_event_text_html' => HtmlTokenDefinition::class,
    );

    public function __construct(
        private readonly SponsorsEventResolver $resolver,
        private readonly TokenDefinitionFactoryInterface $tokenDefinitionFactory,
    ) {
    }

    /**
     * Macht die Tokens in den Formular-Benachrichtigungen als Vorschlag bekannt.
     */
    #[AsEventListener]
    public function onGetTokenDefinitions(GetTokenDefinitionsForNotificationTypeEvent $event): void
    {
        if (FormGeneratorNotificationType::NAME !== $event->getNotificationType()->getName()) {
            return;
        }

        foreach (array_keys(self::TOKENS) as $tokenName) {
            $event->addTokenDefinition($this->getTokenDefinition($tokenName));
        }
    }

    #[AsEventListener]
    public function onCreateParcel(CreateParcelEvent $event): void
    {
        $parcel = $event->getParcel();
        $tokenStamp = $parcel->getStamp(TokenCollectionStamp::class);
        $formStamp = $parcel->getStamp(FormConfigStamp::class);

        if (null === $tokenStamp || null === $formStamp) {
            return;
        }

        $sponsorEvent = $this->resolver->findForCurrentRequest();

        // Nur wenn das Event tatsächlich auf das abgeschickte Formular zeigt – sonst
        // würde ein zufällig vorhandener "token"-Parameter andere Formulare beeinflussen.
        if (null === $sponsorEvent || (int) ($sponsorEvent['form_id'] ?? 0) !== $formStamp->formConfig->getId()) {
            return;
        }

        foreach ($this->getTokenValues($sponsorEvent) as $tokenName => $value) {
            $tokenStamp->tokenCollection->replaceToken(
                $this->getTokenDefinition($tokenName)->createToken($tokenName, $value),
            );
        }
    }

    /**
     * @param array<string, mixed> $sponsorEvent
     *
     * @return array<string, string>
     */
    private function getTokenValues(array $sponsorEvent): array
    {
        $text = str_replace("\r\n", "\n", (string) ($sponsorEvent['notificationText'] ?? ''));

        return array(
            'sponsor_event_title' => SponsorsEventResolver::formatTitle($sponsorEvent),
            'sponsor_event_date' => SponsorsEventResolver::formatDate($sponsorEvent),
            'sponsor_event_time' => SponsorsEventResolver::formatTime($sponsorEvent),
            'sponsor_event_subject' => (string) ($sponsorEvent['notificationSubject'] ?? ''),
            'sponsor_event_text' => $text,
            'sponsor_event_text_html' => nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), false),
        );
    }

    private function getTokenDefinition(string $tokenName): TokenDefinitionInterface
    {
        return $this->tokenDefinitionFactory->create(
            self::TOKENS[$tokenName],
            $tokenName,
            'sponsors_event.'.$tokenName,
        );
    }
}
