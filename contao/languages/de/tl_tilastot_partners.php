<?php

/*
 * This file is part of the HolemaClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_tilastot_partners']['name'] = array('Name', 'Bitte geben Sie den Namen des Partners ein.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['displayname'] = array('Angezeigter Name', 'Falls der Name auf der Webseite anders sein soll, als hier im Backend.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['alias'] = array('Partner-Alias', 'Eindeutige Adresse für die Detailseite. Wird beim Speichern automatisch aus dem Namen erzeugt, wenn das Feld leer bleibt.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['url'] = array('Website URL', 'Beginnt mit https://');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['category'] = array('Kategorie', 'Ein Partner kann auch in mehreren Kategorien eingetragen werden.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['branche'] = array('Branche', 'Die Branche(n), in der der Partner tätig ist. Danach kann die Partnerliste im Frontend gefiltert werden.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['logo'] = array('Partnerlogo', 'Ohne Logo wird nur der angezeigte Name bzw. der Name genutzt.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['photo'] = array('Foto', 'Ein Foto (z. B. Firmengebäude, Team oder Produktbild) für die Detailseite und die Partnerliste.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['teaser'] = array('Kurzbeschreibung', 'Kurzer Text ohne Formatierung, der in der Partnerliste angezeigt wird. Bleibt das Feld leer, wird der Anfang der Beschreibung verwendet.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['description'] = array('Beschreibung', 'Ausführlicher Text mit Formatierungen, der auf der Detailseite des Partners angezeigt wird.');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['published'] = array('Anzeigen', 'Sobald der Haken gesetzt ist, ist der Partner auf der Webseite zu sehen.');

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_tilastot_partners']['general_legend'] = 'Allgemeine Daten';
$GLOBALS['TL_LANG']['tl_tilastot_partners']['category_legend'] = 'Einordnung';
$GLOBALS['TL_LANG']['tl_tilastot_partners']['media_legend'] = 'Bilder';
$GLOBALS['TL_LANG']['tl_tilastot_partners']['text_legend'] = 'Beschreibung';
$GLOBALS['TL_LANG']['tl_tilastot_partners']['publish_legend'] = 'Veröffentlichung';

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_tilastot_partners']['new'] = array('Neuer Partner', 'Neuen Partner erstellen');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['edit'] = array('Partner bearbeiten', 'Partner ID %s bearbeiten');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['copy'] = array('Partner duplizieren', 'Partner ID %s duplizieren');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['delete'] = array('Partner löschen', 'Partner ID %s löschen');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['show'] = array('Partner anzeigen', 'Partner ID %s anzeigen');
$GLOBALS['TL_LANG']['tl_tilastot_partners']['toggle'] = array('Partner anzeige umstellen', 'Sichtbarkeit von Partner ID %s ändern');
