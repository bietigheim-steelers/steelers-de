<?php

namespace App\Dca;

use Contao\Config;
use Contao\DataContainer;
use Contao\Date;

/**
 * DCA-Callbacks für steelers_auktion.
 */
class AuctionBidDca
{
    /**
     * label_callback: Gebot als Betrag und Zeitpunkt des Gebots formatieren.
     *
     * Gebote aus der Zeit vor App\Migration\AuctionMigration haben keinen Zeitstempel.
     *
     * @param array<int, string> $args Spaltenwerte in der Reihenfolge von list.label.fields
     *
     * @return array<int, string>
     */
    public function formatListLabel(array $row, string $label, DataContainer $dc, array $args): array
    {
        $columns = array_flip($GLOBALS['TL_DCA'][$dc->table]['list']['label']['fields']);

        $args[$columns['gebot']] = number_format((float) $row['gebot'], 2, ',', '.') . ' €';
        $args[$columns['tstamp']] = $row['tstamp'] ? Date::parse(Config::get('datimFormat'), (int) $row['tstamp']) : '–';

        return $args;
    }
}
