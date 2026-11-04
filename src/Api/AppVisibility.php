<?php

namespace App\Api;

use Contao\Model;

/**
 * Filtert Screens, Bausteine und Schalter nach Plattform und App-Version der anfragenden App.
 * Veröffentlicht/Start/Stop prüft bereits die SQL-Bedingung (AbstractApiController::publishedCondition).
 *
 * Fehlen `platform`/`appVersion` in der Anfrage (z. B. beim Testen im Browser), wird nicht danach gefiltert.
 * Echte Apps senden beides immer mit.
 */
class AppVisibility
{
    public function isVisible(Model $row, ?string $platform, ?string $appVersion): bool
    {
        $rowPlatform = (string) $row->platform;
        if ($rowPlatform !== '' && $platform !== null && $rowPlatform !== $platform) {
            return false;
        }

        $min = (string) $row->minAppVersion;
        if ($min !== '' && $appVersion !== null && version_compare($appVersion, $min, '<')) {
            return false;
        }

        return true;
    }

    public static function platform(?string $value): ?string
    {
        return \in_array($value, ['ios', 'android'], true) ? $value : null;
    }

    public static function version(?string $value): ?string
    {
        return $value !== null && preg_match('/^\d+(\.\d+){0,2}/', $value, $m) ? $m[0] : null;
    }
}
