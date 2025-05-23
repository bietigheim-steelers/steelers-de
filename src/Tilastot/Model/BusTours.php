<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tilastot\Model;

use Contao\Model;

class BusTours extends Model
{
    /**
     * Name of the database table
     * @var string
     */
    protected static $strTable = 'tl_tilastot_bus_tours';
}
