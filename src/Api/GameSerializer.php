<?php

namespace App\Api;

use App\Model\Games;
use App\Model\Standings;

/**
 * Wandelt Spiele und Teams in die API-Struktur (docs/openapi.yaml: Game, Team).
 */
class GameSerializer
{
    /** Nach dieser Zeit ohne "ended"-Flag gilt ein begonnenes Spiel nicht mehr als live. */
    private const LIVE_WINDOW = 4 * 3600;

    private const RESULT_TYPES = [
        '' => 'regulation',
        'OT' => 'overtime',
        'SO' => 'shootout',
        'OR' => 'awarded',
    ];

    /** @var array<int, array<int, array>> round => tilastotid => team */
    private array $teams = [];

    public function __construct(
        private readonly ImageUrlBuilder $images,
        private readonly ContentFormatter $format,
    ) {
    }

    public function serialize(Games $game): array
    {
        $round = (int) $game->round;
        $start = $this->startTimestamp($game);
        $hasScore = $game->homescore !== null && $game->awayscore !== null;

        $tickets = null;
        if ($game->eventimurl || $game->magentaurl) {
            $tickets = [
                'eventimUrl' => $game->eventimurl ?: null,
                'magentaUrl' => $game->magentaurl ?: null,
            ];
        }

        preg_match_all('/\d+\s*:\s*\d+/', (string) $game->periodscore, $periods);

        return [
            'id' => (int) $game->id,
            'round' => $round,
            'gameday' => $game->gameday !== null && $game->gameday !== '' ? (int) $game->gameday : null,
            'startsAt' => $this->format->date($start),
            'location' => $this->format->text($game->location),
            'spectators' => $game->spectators !== null ? (int) $game->spectators : null,
            'home' => $this->team($round, (int) $game->hometeam),
            'away' => $this->team($round, (int) $game->awayteam),
            'homeScore' => $hasScore ? (int) $game->homescore : null,
            'awayScore' => $hasScore ? (int) $game->awayscore : null,
            'periodScores' => array_map(static fn ($p) => preg_replace('/\s+/', '', $p), $periods[0]),
            'resultType' => $hasScore ? (self::RESULT_TYPES[(string) $game->resulttype] ?? 'regulation') : null,
            'status' => $this->status($game, $start, $hasScore),
            'optional' => (bool) $game->optional,
            'tickets' => $tickets,
        ];
    }

    /**
     * Beginn als Zeitstempel. `gamedate` ist je nach Import unterschiedlich gespeichert: die Berliner
     * Uhrzeit als echter Zeitpunkt (aeltere Runden, Backend) oder als UTC-Uhrzeit (aktuelle Importe,
     * dann liegt der Beginn 1–2 Stunden zu spaet). Das Feld `gametime` ("19:30") ist in beiden Faellen
     * richtig und entscheidet, welche Variante vorliegt.
     */
    private function startTimestamp(Games $game): int
    {
        $ts = (int) $game->gamedate;
        if ($ts === 0 || !preg_match('/^(\d{1,2}):(\d{2})$/', trim((string) $game->gametime), $m)) {
            return $ts;
        }

        $berlin = new \DateTimeZone('Europe/Berlin');
        $wanted = \sprintf('%02d:%02d', $m[1], $m[2]);
        $instant = new \DateTimeImmutable('@' . $ts);

        if ($instant->setTimezone($berlin)->format('H:i') === $wanted) {
            return $ts;
        }
        if ($instant->format('H:i') === $wanted) {
            return (new \DateTimeImmutable($instant->format('Y-m-d') . ' ' . $wanted, $berlin))->getTimestamp();
        }

        return $ts;
    }

    /**
     * Status aus dem "ended"-Flag des Imports; ohne Flag gilt ein Spiel bis zu vier Stunden nach
     * Beginn als live, danach als beendet, sobald ein Ergebnis vorliegt.
     */
    public function status(Games $game, int $start, bool $hasScore): string
    {
        if ($game->ended) {
            return 'finished';
        }

        $now = time();
        if ($start > $now) {
            return 'upcoming';
        }
        if ($now - $start <= self::LIVE_WINDOW) {
            return 'live';
        }

        return $hasScore ? 'finished' : 'upcoming';
    }

    public function team(int $round, int $tilastotId): array
    {
        $team = $this->teamsOfRound($round)[$tilastotId] ?? null;

        return $team ?? ['id' => $tilastotId, 'name' => '', 'shortName' => null, 'city' => null, 'logo' => null];
    }

    public function teamFromModel(Standings $standings): array
    {
        return [
            'id' => (int) $standings->tilastotid,
            'name' => $this->format->text($standings->name) ?? '',
            'shortName' => $this->format->text($standings->shortname),
            'city' => $this->format->text($standings->city),
            'logo' => $this->images->fromUuid($standings->logo, 200),
        ];
    }

    /**
     * @return array<int, array>
     */
    private function teamsOfRound(int $round): array
    {
        if (!isset($this->teams[$round])) {
            $this->teams[$round] = [];
            foreach (Standings::findBy(['round=?'], [$round]) ?? [] as $standings) {
                $this->teams[$round][(int) $standings->tilastotid] = $this->teamFromModel($standings);
            }
        }

        return $this->teams[$round];
    }
}
