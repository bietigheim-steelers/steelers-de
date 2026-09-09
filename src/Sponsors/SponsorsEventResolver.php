<?php

declare(strict_types=1);

namespace App\Sponsors;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Ermittelt das Sponsor-Event zum Zugriffstoken der laufenden Anfrage.
 *
 * Ein Formular kann von mehreren Sponsor-Events gleichzeitig genutzt werden; welches
 * Event gemeint ist, steht ausschließlich im URL-Parameter "token" des Zugriffslinks
 * (siehe App\Dca\SponsorsEventDca::generateAccessLink). Contao-Formulare senden per
 * POST an die aktuelle URL, der Parameter ist deshalb auch beim Absenden noch da –
 * darauf bauen die Ersetzungen für Bestätigungsseite und E-Mail-Benachrichtigung auf.
 */
class SponsorsEventResolver
{
    /**
     * Innerhalb einer Anfrage wird höchstens einmal abgefragt (Insert-Tags und
     * Benachrichtigungs-Tokens greifen beide zu).
     *
     * @var array<string, array<string, mixed>|null>
     */
    private array $cache = array();

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForCurrentRequest(): array|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return null;
        }

        $token = $request->query->get('token');

        if (!\is_string($token) || '' === $token) {
            return null;
        }

        return $this->cache[$token] ??= $this->connection->fetchAssociative(
            'SELECT * FROM tl_sponsors_event WHERE access_token = ? AND published = 1',
            array($token),
        ) ?: null;
    }

    /**
     * Der Titel wird – anders als notificationSubject/notificationText – mit kodierten
     * Entities gespeichert und muss für Text-Ausgaben zurückgewandelt werden.
     *
     * @param array<string, mixed> $sponsorEvent
     */
    public static function formatTitle(array $sponsorEvent): string
    {
        return StringUtil::decodeEntities((string) ($sponsorEvent['title'] ?? ''));
    }

    /**
     * Datum wie im Formular-Template (format_date mit "dd.MM.yyyy").
     *
     * @param array<string, mixed> $sponsorEvent
     */
    public static function formatDate(array $sponsorEvent): string
    {
        if (empty($sponsorEvent['startDate'])) {
            return '';
        }

        return date('d.m.Y', (int) $sponsorEvent['startDate']);
    }

    /**
     * Uhrzeit wie im Formular-Template (format_time mit "HH:mm"). Contao speichert die
     * Startzeit als Zeitstempel, nicht als "19:00".
     *
     * @param array<string, mixed> $sponsorEvent
     */
    public static function formatTime(array $sponsorEvent): string
    {
        if ('' === (string) ($sponsorEvent['startTime'] ?? '')) {
            return '';
        }

        return date('H:i', (int) $sponsorEvent['startTime']);
    }
}
