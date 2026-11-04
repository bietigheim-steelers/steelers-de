<?php

use Contao\System;

System::loadLanguageFile('tl_app_common');

$GLOBALS['TL_LANG']['tl_app_flag']['flag_legend'] = 'Schalter';
$GLOBALS['TL_LANG']['tl_app_flag']['publish_legend'] = &$GLOBALS['TL_LANG']['tl_app_common']['publish_legend'];

$GLOBALS['TL_LANG']['tl_app_flag']['flagKey'] = ['Schlüssel', 'Name des Schalters, wie ihn die App kennt, z. B. "ticketShop". Erscheint in /app/config unter "flags".'];
$GLOBALS['TL_LANG']['tl_app_flag']['flagValue'] = ['Wert', '1 / ja / true = an, 0 / nein / false = aus, eine Zahl bleibt Zahl, alles andere ist Text. Ein nicht veröffentlichter Schalter fehlt in der Antwort.'];
$GLOBALS['TL_LANG']['tl_app_flag']['description'] = ['Beschreibung', 'Wofür der Schalter gedacht ist (nur zur Orientierung im Backend).'];

foreach (['published', 'start', 'stop', 'minAppVersion', 'platform'] as $field) {
    $GLOBALS['TL_LANG']['tl_app_flag'][$field] = &$GLOBALS['TL_LANG']['tl_app_common'][$field];
}

$GLOBALS['TL_LANG']['tl_app_flag']['new'] = ['Neuer Schalter', 'Einen neuen Schalter anlegen'];
$GLOBALS['TL_LANG']['tl_app_flag']['edit'] = ['Schalter bearbeiten', 'Schalter ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_app_flag']['copy'] = ['Schalter duplizieren', 'Schalter ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_app_flag']['delete'] = ['Schalter löschen', 'Schalter ID %s löschen'];
$GLOBALS['TL_LANG']['tl_app_flag']['toggle'] = ['Veröffentlichen/Verbergen', 'Schalter ID %s veröffentlichen/verbergen'];
$GLOBALS['TL_LANG']['tl_app_flag']['show'] = ['Details', 'Details des Schalters ID %s anzeigen'];
