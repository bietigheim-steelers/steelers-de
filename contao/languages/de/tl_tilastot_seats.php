<?php

/**
 * Felder der Sitzplatzverwaltung.
 *
 * Diese Datei schrieb bisher nach $GLOBALS['TL_LANG']['tl_tilastot_season_ticket']
 * und doppelte damit nur die Labels aus tl_tilastot_season_ticket.php -- das DCA
 * tl_tilastot_seats las seine Labels also nie und zeigte die Feldnamen.
 */
$GLOBALS['TL_LANG']['tl_tilastot_seats'] = [
  'seat_block' => ['Block', 'Block des Sitzplatzes'],
  'seat_row' => ['Reihe', 'Reihe des Sitzplatzes'],
  'seat_seat' => ['Sitz', 'Sitznummer'],
  'seat_status' => [
    'Status des Platzes',
    'verfügbar, reserviert (Vorbehalt für den bisherigen Inhaber), in Bestellung, verkauft oder nicht existent',
  ],
  'reserved_until' => [
    'Reservierung läuft ab',
    'Nur bei Status „in Bestellung": bis wann der Platz für einen laufenden Bestellvorgang gehalten wird.',
  ],
];
