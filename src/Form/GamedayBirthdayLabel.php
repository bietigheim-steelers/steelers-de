<?php

declare(strict_types=1);

namespace App\Form;

use App\Model\Games;
use App\Model\Standings;
use Contao\Date;

/**
 * Beschriftung eines Spiels für die Geburtstagsgrüße, z. B.
 * "Fr 23.10.2026 - Ravensburg Towerstars".
 *
 * Wird an zwei Stellen gebraucht und muss dort identisch aussehen: im Auswahlfeld
 * des Formulars (App\EventListener\FormField\GamedayBirthdayListener) und in der
 * E-Mail, in der die abgeschickte Spiel-ID durch den lesbaren Text ersetzt wird
 * (App\EventListener\BirthdayGreetingsFormListener).
 */
final class GamedayBirthdayLabel
{
    /**
     * @param array<string, mixed> $game Datensatz aus tl_tilastot_client_games
     */
    public static function fromRow(array $game): string
    {
        $label = Date::parse('D d.m.Y', $game['gamedate']);
        $away = Standings::findByIdAndRound($game['awayteam'], $game['round'], true);

        if (isset($away['name'])) {
            $label .= ' - ' . $away['name'];
        }

        return $label;
    }

    /**
     * Gibt null zurück, wenn es das Spiel nicht (mehr) gibt.
     */
    public static function fromGameId(int $gameId): ?string
    {
        $game = Games::findById($gameId);

        return null === $game ? null : self::fromRow($game->row());
    }
}
