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

use Contao\Database;
use Contao\Model;
use Contao\System;
use App\Model\Rounds;

class Standings extends Model
{

	/**
	 * Name of the table
	 * @var string
	 */
	protected static $strTable = 'tl_tilastot_client_standings';
	protected static $localCache = array();

	public function findTeamsForSelect($dc)
	{
		$ret = array();
		$ret[-1] = "";
		if ($dc->activeRecord->round && Standings::findByRound($dc->activeRecord->round)) {
			foreach (Standings::findByRound($dc->activeRecord->round) as $team) {
				$ret[$team->tilastotid] = $team->name;
			}
		} else {
			foreach (Standings::findAll() as $team) {
				$ret[$team->tilastotid] = $team->name;
			}
		}

		return $ret;
	}

	public function findTeamsForDisplay($row, $label, $dc, $args)
	{
		$args[0] = date('d.m.Y', $args[0]);
		$args[1] = self::getTeamData($args[1], $args[3]);
		$args[2] = self::getTeamData($args[2], $args[3]);
		$args[3] = Rounds::findForDisplay($args[3]);
		return $args;
	}

	public static function getTeamData($tilastotId, $round, $data = 'name')
	{
		$team = self::findAll(array(
			'limit'   => 1,
			'column'  => array('tilastotid=?', 'round=?'),
			'value'   => array($tilastotId, $round)
		));
		if ($team) {
			$team = $team->fetchAll();
			return $team[0][$data];
		} else {
			return '## unknown team (' . $tilastotId . ', ' . $round . ') ##';
		}
	}

	public function findColumnsForSelect()
	{
		$ret = array();
		$objDatabase = Database::getInstance();
		$res = $objDatabase->prepare("SHOW COLUMNS FROM " . self::$strTable)->execute()->fetchAllAssoc();
		foreach ($res as $column) {
			$ret[$column['Field']] = $column['Field'];
		}
		return $ret;
	}

	public static function findByIdAndRound($tilastotid, $round, $justName = false)
	{
		if (self::$localCache['r' . $round]['t' . $tilastotid]) {
			$return = self::$localCache['r' . $round]['t' . $tilastotid];
		} else {
			$result = Standings::findAll(array(
				'limit'   => 1,
				'column'  => array('tilastotid=?', 'round=?'),
				'value'   => array($tilastotid, $round)
			));
			if ($result) {
				$return = $result->fetchAll()[0];
				if ($return['logo']) {
					$return['logo'] = self::parseLogo($return['logo']);
				}
				self::$localCache['r' . $round]['t' . $tilastotid] = $return;
			} else {
				return null;
			}
		}
		if ($justName) {
			$removeKeys = array('id', 'tilastotid', 'round', 'tstamp', 'city', 'alias', 'games', 'rw', 'ow', 'pw', 'pl', 'ol', 'rl', 'points', 'goalsfor', 'goalsagainst', 'penalties');
			foreach ($removeKeys as $key) {
				unset($return[$key]);
			}
		}
		return $return;
	}

	public static function getLogoFilename($alias, $width)
	{
		return '/files/holema_logos/' . $alias . "_" . $width . ".png";
	}

	public static function parseLogo($binary)
	{
		if ($binary) {
			$figureBuilder = System::getContainer()
				->get('contao.image.studio')
				->createFigureBuilder()
				->from($binary);
			$figure = $figureBuilder->buildIfResourceExists();
			if($figure) {
				return $figure->getImage()->getImageSrc();
			}
		}
		return null;
	}
}