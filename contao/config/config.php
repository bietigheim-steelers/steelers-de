<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Model\Rounds;
use App\Model\Games;
use App\Model\Standings;
use App\Model\Players;
use App\Model\PlayerStats;
use App\Module\RefreshModule;
use App\Model\Partners;
use App\Model\Camps;
use App\Model\BusTours;

/* Backend Module */

$GLOBALS['BE_MOD']['del'] = array(
	'tilastot_rounds' => array(
		'tables' => array('tl_tilastot_client_rounds')
	),
	'tilastot_standings' => array(
		'tables' => array('tl_tilastot_client_standings')
	),
	'tilastot_games' => array(
		'tables' => array('tl_tilastot_client_games')
	),
	'tilastot_players' => array(
		'tables' => array('tl_tilastot_client_players', 'tl_tilastot_client_stats')
	),
	'tilastot_refresh' => array(
		'callback' => RefreshModule::class
	)
);

$GLOBALS['BE_MOD']['content']['tilastot_partners'] = array('tables' => array('tl_tilastot_partners'));
$GLOBALS['BE_MOD']['content']['tilastot_camps'] = array('tables' => array('tl_tilastot_camps'));
$GLOBALS['BE_MOD']['content']['tilastot_bus_tours'] = array('tables' => array('tl_tilastot_bus_tours'));

/* Model Classes */
$GLOBALS['TL_MODELS']['tl_tilastot_client_rounds'] = Rounds::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_games'] = Games::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_players'] = Players::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_stats'] = PlayerStats::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_standings'] = Standings::class;
$GLOBALS['TL_MODELS']['tl_tilastot_partners'] = Partners::class;
$GLOBALS['TL_MODELS']['tl_tilastot_camps'] = Camps::class;
$GLOBALS['TL_MODELS']['tl_tilastot_bus_tours'] = BusTours::class;

/* Cronjob */
$GLOBALS['TL_CRON']['hourly'][] = array('App\\Utils\\TilastotApi', 'refreshAll');

/* Wrapper */
$GLOBALS['TL_WRAPPERS']['start'][] = 'rowStart';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'rowEnd';

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_camps';
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_campsp';
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_bus_tours';
