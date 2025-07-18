<?php

/* Wrapper */
$GLOBALS['TL_WRAPPERS']['start'][] = 'rowStart';
$GLOBALS['TL_WRAPPERS']['stop'][] = 'rowEnd';

$GLOBALS['TL_MODELS']['tl_tilastot_client_games'] = App\Model\Games::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_standings'] = App\Model\Standings::class;
$GLOBALS['TL_MODELS']['tl_tilastot_client_rounds'] = App\Model\Rounds::class;
