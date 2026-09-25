<?php

use App\Model\Rounds;
use App\Model\Games;
use App\Model\Standings;
use App\Model\Players;
use App\Model\PlayerStats;
use App\Controller\FrontendModule\RefreshModule;
use App\Controller\Backend\ContentImportAction;
use App\Model\Partners;
use App\Model\Camps;
use App\Model\SeasonTicket;
use App\Model\Seats;
use App\Model\SponsorsEvent;

/* Backend Module */

$GLOBALS['BE_MOD']['saison'] = array(
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
$GLOBALS['BE_MOD']['content']['tilastot_seats'] = array('tables' => array('tl_tilastot_seats'));
$GLOBALS['BE_MOD']['content']['tilastot_season_ticket'] = array('tables' => array('tl_tilastot_season_ticket'));
$GLOBALS['BE_MOD']['content']['sponsors_events'] = array('tables' => array('tl_sponsors_event'));
$GLOBALS['BE_MOD']['content']['auction_bids'] = array('tables' => array('steelers_auktion'));

/*
 * "Content-Import"-Icon neben jedem Artikel im Artikelbaum einer Seite
 * (tl_article, key=content_import), siehe contao/dca/tl_article.php. Läuft
 * immer im Kontext des angeklickten Artikels (Input::get('id') = dessen
 * eigene ID, per Zeilen-Operation automatisch gesetzt), deshalb kein
 * eigener Menüpunkt.
 */
$GLOBALS['BE_MOD']['content']['article']['content_import'] = array(ContentImportAction::class, 'run');

/* Model Classes */
$GLOBALS['TL_MODELS']['tl_tilastot_client_rounds'] = Rounds::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_games'] = Games::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_players'] = Players::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_stats'] = PlayerStats::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_standings'] = Standings::class;
$GLOBALS['TL_MODELS']['tl_tilastot_partners'] = Partners::class;
$GLOBALS['TL_MODELS']['tl_tilastot_camps'] = Camps::class;
$GLOBALS['TL_MODELS']['tl_tilastot_seats'] = Seats::class;
$GLOBALS['TL_MODELS']['tl_tilastot_season_ticket'] = SeasonTicket::class;
$GLOBALS['TL_MODELS']['tl_sponsors_event'] = SponsorsEvent::class;

/* Wrapper */
$GLOBALS['TL_WRAPPERS']['start'][] = 'wrapper_block_start_element';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'wrapper_block_end_element';

// Add permissions
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_camps';
$GLOBALS['TL_PERMISSIONS'][] = 'tilastot_campsp';
