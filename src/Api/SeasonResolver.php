<?php

namespace App\Api;

use App\Model\Games;

/**
 * Bestimmt die Runde, wenn die App keine angibt: die des naechsten anstehenden Spiels,
 * sonst die des letzten Spiels. Contao kennt keine globale "aktuelle Runde" (die Module
 * wählen sie einzeln), deshalb ist das eine Annahme – die App kann `round` immer mitschicken.
 */
class SeasonResolver
{
    public function currentRound(): ?int
    {
        $next = Games::findOneBy(['gamedate >= ?'], [time()], ['order' => 'gamedate ASC']);
        if ($next !== null) {
            return (int) $next->round;
        }

        $last = Games::findOneBy(['gamedate > 0'], [], ['order' => 'gamedate DESC']);

        return $last !== null ? (int) $last->round : null;
    }
}
