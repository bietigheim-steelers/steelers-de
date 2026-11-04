<?php

namespace App\Model;

use Contao\Model;

/**
 * Schalter der App (Backend-Bereich "App" > Schalter), erscheint als flags.<flagKey> in /app/config.
 *
 * @property int    $id
 * @property int    $tstamp
 * @property string $flagKey
 * @property string $flagValue
 * @property string $description
 * @property bool   $published
 * @property string $start
 * @property string $stop
 * @property string $minAppVersion
 * @property string $platform
 */
class AppFlag extends Model
{
    protected static $strTable = 'tl_app_flag';
}
