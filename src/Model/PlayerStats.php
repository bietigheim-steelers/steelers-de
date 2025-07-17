<?php

/*
 * This file is part of the TilastotClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tilastot\Model;

use Contao\Database;
use Contao\Model;

class PlayerStats extends Model
{

	/**
	 * Name of the table
	 * @var string
	 */
	protected static $strTable = 'tl_tilastot_client_stats';
	public static function findForSelect()
	{
		$ret = array();
		$ret[-1] = '';

		foreach (PlayerStats::findAll() as $player) {
			$ret[$player->id] = $player->lastname . ", " . $player->firstname;
		}

		return $ret;
	}

}
