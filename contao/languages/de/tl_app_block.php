<?php

use Contao\System;

System::loadLanguageFile('tl_app_common');

$GLOBALS['TL_LANG']['tl_app_block']['type_legend'] = 'Baustein';
$GLOBALS['TL_LANG']['tl_app_block']['publish_legend'] = &$GLOBALS['TL_LANG']['tl_app_common']['publish_legend'];

$GLOBALS['TL_LANG']['tl_app_block']['types'] = [
    'headline' => 'Überschrift',
    'text' => 'Text',
    'image' => 'Bild',
    'list' => 'Liste',
    'button' => 'Button',
    'download' => 'Download',
    'video' => 'Video',
    'promo' => 'Promo-Banner',
    'news_list' => 'News-Liste',
    'next_game' => 'Nächstes Spiel',
    'games' => 'Spiele',
    'standings' => 'Tabelle',
    'roster' => 'Kader',
    'partners' => 'Partner',
    'article' => 'Artikel der Webseite einbinden',
    'content_element' => 'Inhaltselement der Webseite einbinden',
];
$GLOBALS['TL_LANG']['tl_app_block']['layouts'] = ['list' => 'Liste', 'carousel' => 'Karussell', 'grid' => 'Raster', 'slider' => 'Slider'];
$GLOBALS['TL_LANG']['tl_app_block']['statuses'] = ['upcoming' => 'Kommende Spiele', 'finished' => 'Beendete Spiele'];

$GLOBALS['TL_LANG']['tl_app_block']['blockType'] = ['Typ', 'Inhalts-Bausteine bringen ihren Inhalt mit. Daten-Bausteine (News-Liste bis Partner) werden von der App selbst mit Daten gefüllt; hier steht nur die Konfiguration. Artikel/Inhaltselement binden vorhandene Webseiten-Inhalte ein (Text, Bild, Liste, Link, Download, YouTube/Vimeo; alles andere entfällt).'];
$GLOBALS['TL_LANG']['tl_app_block']['blockTitle'] = ['Titel / Text', 'Bei „Überschrift“ der Text der Überschrift, sonst die optionale Überschrift über dem Baustein.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockText'] = ['Text', 'Erlaubt sind Absätze, fett, kursiv, Listen und Links. Alles andere entfernt der Server.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockPlain'] = ['Text', 'Kurzer Text ohne Formatierung.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockLevel'] = ['Ebene', 'Größe der Überschrift (h1 = größte).'];
$GLOBALS['TL_LANG']['tl_app_block']['blockImage'] = ['Bild', 'Das Bild wird für die App verkleinert ausgeliefert.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockFile'] = ['Datei', 'Die Datei, die zum Download angeboten wird.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockAlt'] = ['Alternativtext', 'Beschreibung des Bildes für Screenreader.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockCaption'] = ['Bildunterschrift', ''];
$GLOBALS['TL_LANG']['tl_app_block']['blockUrl'] = ['Link', 'https://… oder eine Seite der Webseite (Seitenwähler). Für einen Screen der App: app://screen/<Schlüssel>.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockLabel'] = ['Beschriftung', 'Text des Buttons bzw. des Downloads.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockItems'] = ['Einträge', 'Ein Eintrag pro Zeile.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockOrdered'] = ['Nummeriert', 'Als nummerierte statt als Aufzählungsliste anzeigen.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockProvider'] = ['Anbieter', ''];
$GLOBALS['TL_LANG']['tl_app_block']['blockVideoId'] = ['Video-ID', 'Nur die ID, nicht die ganze Adresse (bei YouTube der Teil nach „v=“).'];
$GLOBALS['TL_LANG']['tl_app_block']['blockLimit'] = ['Anzahl', 'Maximale Anzahl Einträge. 0 = Standard der App.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockCategory'] = ['News-Kategorie', 'Alias einer News-Kategorie. Leer = alle.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockPartnerCategory'] = ['Partner-Kategorie', 'Leer = alle Partner.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockLayout'] = ['Darstellung', 'News: Liste oder Karussell. Partner: Raster oder Slider. Spiele: Liste oder Slider (letztes Spiel, nächstes Heimspiel, nächstes weiteres Spiel; Status und Anzahl werden dann ignoriert). Andere Werte ignoriert der Server.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockStatus'] = ['Welche Spiele', ''];
$GLOBALS['TL_LANG']['tl_app_block']['blockCompact'] = ['Kompakt', 'Nur Platz, Team und Punkte anzeigen.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockPosition'] = ['Position', 'Nur diese Position (wie im Kader gepflegt, z. B. G, D, F). Leer = alle.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockArticle'] = ['Artikel', 'Die Inhaltselemente dieses Artikels erscheinen in der App; was die App nicht darstellen kann, entfällt.'];
$GLOBALS['TL_LANG']['tl_app_block']['blockContent'] = ['Inhaltselement-ID', 'ID eines einzelnen Inhaltselements der Webseite (steht in der Elementliste). Es muss selbst veröffentlicht sein.'];

foreach (['published', 'start', 'stop', 'minAppVersion', 'platform'] as $field) {
    $GLOBALS['TL_LANG']['tl_app_block'][$field] = &$GLOBALS['TL_LANG']['tl_app_common'][$field];
}

$GLOBALS['TL_LANG']['tl_app_block']['new'] = ['Neuer Baustein', 'Einen neuen Baustein anlegen'];
$GLOBALS['TL_LANG']['tl_app_block']['edit'] = ['Baustein bearbeiten', 'Baustein ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_app_block']['copy'] = ['Baustein duplizieren', 'Baustein ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_app_block']['cut'] = ['Baustein verschieben', 'Baustein ID %s verschieben'];
$GLOBALS['TL_LANG']['tl_app_block']['delete'] = ['Baustein löschen', 'Baustein ID %s löschen'];
$GLOBALS['TL_LANG']['tl_app_block']['toggle'] = ['Veröffentlichen/Verbergen', 'Baustein ID %s veröffentlichen/verbergen'];
$GLOBALS['TL_LANG']['tl_app_block']['show'] = ['Details', 'Details des Bausteins ID %s anzeigen'];
