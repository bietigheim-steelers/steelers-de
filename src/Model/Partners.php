<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use Contao\Model;

class Partners extends Model
{

    /**
     * Name of the table
     * @var string
     */
    protected static $strTable = 'tl_tilastot_partners';

    public static function getCategoryOptions($dc = null)
    {
        return array(
            'platin' => 'Platinpartner',
            'premium' => 'Premiumpartner',
            'gold' => 'Goldpartner',
            'silber' => 'Silberpartner',
            'bronze' => 'Bronzepartner',
            'business' => 'Businesspartner',
            'lounge' => 'Businesslounge',
            'medien' => 'Medienpartner',
            'video' => 'Videopartner',
            'carpool' => 'Carpool Partner',
            'team' => 'Teampartner',
            'supporter' => 'Supporter',
            'nachwuchs_haupt' => 'Nachwuchs - Hauptsponsor',
            'nachwuchs' => 'Nachwuchssponsor',
            'nachwuchsspieler' => 'Nachwuchs Spielerpatenschaften',
        );
    }
}
