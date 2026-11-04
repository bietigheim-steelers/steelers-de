<?php

namespace App\Model;

use Contao\Model;

/**
 * Screen, Tab oder Menüeintrag der App (Backend-Bereich "App").
 *
 * @property int    $id
 * @property int    $tstamp
 * @property int    $sortOrder
 * @property string $screenKey
 * @property string $title
 * @property string $icon
 * @property string $placement  tab|menu|hidden
 * @property string $kind       screen|native|link|group
 * @property string $parentKey  Schlüssel der Gruppe (kind=group), der der Eintrag zugeordnet ist; '' = keine
 * @property string $url
 * @property bool   $isHome
 * @property bool   $published
 * @property string $start
 * @property string $stop
 * @property string $minAppVersion
 * @property string $platform   ''|ios|android
 */
class AppScreen extends Model
{
    protected static $strTable = 'tl_app_screen';
}
