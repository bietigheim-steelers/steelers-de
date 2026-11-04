<?php

use Contao\System;

System::loadLanguageFile('tl_app_common');

$GLOBALS['TL_LANG']['tl_app_screen']['screen_legend'] = 'Screen';
$GLOBALS['TL_LANG']['tl_app_screen']['publish_legend'] = &$GLOBALS['TL_LANG']['tl_app_common']['publish_legend'];

$GLOBALS['TL_LANG']['tl_app_screen']['title'] = ['Titel', 'Überschrift des Screens bzw. Beschriftung im Tab oder Menü.'];
$GLOBALS['TL_LANG']['tl_app_screen']['screenKey'] = ['Schlüssel', 'Eindeutige Kennung (Kleinbuchstaben, Ziffern, - und _), z. B. "news" oder "start". Die App verweist darüber auf den Screen (app://screen/<Schlüssel>). Nach der Veröffentlichung nicht mehr ändern.'];
$GLOBALS['TL_LANG']['tl_app_screen']['kind'] = ['Art', 'Screen = Inhalte aus den Bausteinen, Eingebaute Funktion = fest in der App vorhandene Seite (Schlüssel muss der App bekannt sein, z. B. "settings"), Link = externe Webseite, Gruppe = aufklappbarer Menüpunkt, dem andere Einträge zugeordnet werden (nur im Menü).'];
$GLOBALS['TL_LANG']['tl_app_screen']['placement'] = ['Platzierung', 'Tab = untere Navigation (3–5 Einträge), Menü = unter „Mehr“, Nur verlinkt = nicht in der Navigation, aber über Banner/Buttons erreichbar.'];
$GLOBALS['TL_LANG']['tl_app_screen']['parentKey'] = ['Gruppe', 'Ordnet den Eintrag einer Gruppe zu: Er erscheint dann nur aufgeklappt unter dieser Gruppe im Menü (Platzierung wird ignoriert). Nur eine Ebene; Gruppen selbst lassen sich nicht zuordnen. Wird die Gruppe unveröffentlicht oder ausgeblendet, entfallen auch ihre Einträge.'];
$GLOBALS['TL_LANG']['tl_app_screen']['icon'] = ['Icon', 'Schlüssel aus dem Icon-Satz der App (z. B. "news", "calendar"). Unbekannte Schlüssel zeigt die App ohne Icon.'];
$GLOBALS['TL_LANG']['tl_app_screen']['sortOrder'] = ['Reihenfolge', 'Kleinere Zahlen stehen weiter vorne.'];
$GLOBALS['TL_LANG']['tl_app_screen']['isHome'] = ['Startscreen', 'Diesen Screen beim Öffnen der App zeigen. Sind mehrere markiert, gilt der erste in der Reihenfolge; ohne Markierung der erste Tab.'];
$GLOBALS['TL_LANG']['tl_app_screen']['url'] = ['Adresse', 'Vollständige https://-Adresse der Seite, die im Browser geöffnet wird.'];

foreach (['published', 'start', 'stop', 'minAppVersion', 'platform'] as $field) {
    $GLOBALS['TL_LANG']['tl_app_screen'][$field] = &$GLOBALS['TL_LANG']['tl_app_common'][$field];
}

$GLOBALS['TL_LANG']['tl_app_screen']['placements'] = ['tab' => 'Tab', 'menu' => 'Menü', 'hidden' => 'Nur verlinkt'];
$GLOBALS['TL_LANG']['tl_app_screen']['kinds'] = ['screen' => 'Screen', 'native' => 'Eingebaute Funktion', 'link' => 'Link', 'group' => 'Gruppe'];

$GLOBALS['TL_LANG']['tl_app_screen']['new'] = ['Neuer Screen', 'Einen neuen Screen anlegen'];
$GLOBALS['TL_LANG']['tl_app_screen']['edit'] = ['Screen bearbeiten', 'Screen ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_app_screen']['children'] = ['Bausteine', 'Bausteine des Screens ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_app_screen']['copy'] = ['Screen duplizieren', 'Screen ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_app_screen']['delete'] = ['Screen löschen', 'Screen ID %s löschen'];
$GLOBALS['TL_LANG']['tl_app_screen']['toggle'] = ['Veröffentlichen/Verbergen', 'Screen ID %s veröffentlichen/verbergen'];
$GLOBALS['TL_LANG']['tl_app_screen']['show'] = ['Details', 'Details des Screens ID %s anzeigen'];
