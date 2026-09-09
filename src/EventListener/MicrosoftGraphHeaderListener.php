<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mailer\Event\FailedMessageEvent;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\Event\SentMessageEvent;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\RawMessage;
use Terminal42\NotificationCenterBundle\Gateway\MailerGateway;

/**
 * Microsoft Graph lehnt Internet-Message-Header ab, die nicht mit "X-" beginnen:
 *
 *   InvalidInternetMessageHeader: The internet message header name
 *   'Notification-Center-Parcel-ID' should start with 'x-' or 'X-'. (HTTP 400)
 *
 * MicrosoftGraphApiTransport::getMessageCustomHeaders() reicht alle Header
 * ungefiltert als "internetMessageHeaders" weiter, und das Notification Center
 * setzt "Notification-Center-Parcel-ID", um die Zustellquittung zuzuordnen.
 * Anders als der Attachment-Header wird er nicht vor dem Versand entfernt,
 * sondern erst danach in MailerAsynchronousReceiptUpdateListener ausgelesen.
 *
 * Deshalb wird er hier nur fuer die Dauer des Versands umbenannt und danach
 * zurueckbenannt, bevor das Notification Center ihn liest. Loeschen wuerde das
 * Delivery-Tracking stillschweigend ausschalten.
 *
 * Betrifft nur die Graph-Transports; ueber SMTP bleibt der Header unangetastet.
 */
final class MicrosoftGraphHeaderListener
{
    private const ORIGINAL_NAME = MailerGateway::MESSAGE_IDENTIFIER_HEADER;

    private const PREFIXED_NAME = 'X-'.MailerGateway::MESSAGE_IDENTIFIER_HEADER;

    /**
     * Laeuft zuletzt, damit andere Listener ihre Header vorher setzen koennen.
     */
    #[AsEventListener(priority: -1000)]
    public function onMessage(MessageEvent $event): void
    {
        // Beim Einreihen in die Queue wird noch nicht verschickt, der Header
        // muss dort erhalten bleiben.
        if ($event->isQueued() || !str_starts_with($event->getTransport(), 'microsoftgraph')) {
            return;
        }

        $this->renameHeader($event->getMessage(), self::ORIGINAL_NAME, self::PREFIXED_NAME);
    }

    /**
     * Laeuft vor MailerAsynchronousReceiptUpdateListener (Prioritaet 0).
     */
    #[AsEventListener(priority: 1000)]
    public function onSentMessage(SentMessageEvent $event): void
    {
        $this->renameHeader($event->getMessage()->getOriginalMessage(), self::PREFIXED_NAME, self::ORIGINAL_NAME);
    }

    #[AsEventListener(priority: 1000)]
    public function onFailedMessage(FailedMessageEvent $event): void
    {
        $this->renameHeader($event->getMessage(), self::PREFIXED_NAME, self::ORIGINAL_NAME);
    }

    private function renameHeader(RawMessage $message, string $from, string $to): void
    {
        if (!$message instanceof Message) {
            return;
        }

        $headers = $message->getHeaders();

        if (!$header = $headers->get($from)) {
            return;
        }

        $headers->remove($from);
        $headers->addTextHeader($to, $header->getBodyAsString());
    }
}
