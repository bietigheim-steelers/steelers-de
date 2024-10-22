<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use App\Tilastot\Model\Rounds;
use App\Tilastot\Model\Games;
use App\Tilastot\Model\Standings;
use App\Tilastot\Model\Players;
use App\Tilastot\Model\PlayerStats;
use \App\Tilastot\Module\RefreshModule;
use App\Tilastot\Model\Partners;
use App\Tilastot\Model\Camps;

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

/* Model Classes */
$GLOBALS['TL_MODELS']['tl_tilastot_client_rounds'] = Rounds::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_games'] = Games::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_players'] = Players::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_stats'] = PlayerStats::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_standings'] = Standings::class;
$GLOBALS['TL_MODELS']['tl_tilastot_partners'] = Partners::class;
$GLOBALS['TL_MODELS']['tl_tilastot_camps'] = Camps::class;

/* Cronjob */
$GLOBALS['TL_CRON']['hourly'][] = array('App\\Tilastot\\Utils\\TilastotApi', 'refreshAll');

/* Wrapper */
$GLOBALS['TL_WRAPPERS']['start'][] = 'rowStart';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'rowEnd';

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_camps';
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_campsp';
