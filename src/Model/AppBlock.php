<?php

namespace App\Model;

use Contao\Model;

/**
 * Baustein eines App-Screens (Tabelle tl_app_block, Kinder von tl_app_screen).
 *
 * @property int    $id
 * @property int    $pid
 * @property int    $sorting
 * @property int    $tstamp
 * @property string $blockType
 * @property string $blockTitle
 * @property string $blockText
 * @property string $blockPlain
 * @property string $blockLevel
 * @property string $blockImage
 * @property string $blockFile
 * @property string $blockAlt
 * @property string $blockCaption
 * @property string $blockUrl
 * @property string $blockLabel
 * @property string $blockItems
 * @property bool   $blockOrdered
 * @property string $blockProvider
 * @property string $blockVideoId
 * @property int    $blockLimit
 * @property string $blockCategory         News-Kategorie (Alias)
 * @property string $blockPartnerCategory  Partner-Kategorie (Schlüssel)
 * @property string $blockLayout
 * @property string $blockStatus
 * @property bool   $blockCompact
 * @property string $blockPosition
 * @property int    $blockArticle
 * @property int    $blockContent
 * @property bool   $published
 * @property string $start
 * @property string $stop
 * @property string $minAppVersion
 * @property string $platform
 */
class AppBlock extends Model
{
    protected static $strTable = 'tl_app_block';
}
