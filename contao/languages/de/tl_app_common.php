<?php

/*
 * Gemeinsame Texte des Backend-Bereichs "App" (Sichtbarkeitsfelder von Screens, Bausteinen und Schaltern).
 * Die Tabellen-Sprachdateien (tl_app_*.php) übernehmen die Feldlabels von hier.
 */

$GLOBALS['TL_LANG']['tl_app_common']['platforms'] = ['ios' => 'iOS', 'android' => 'Android'];
$GLOBALS['TL_LANG']['tl_app_common']['versionInvalid'] = 'Bitte eine Version wie 1.2 oder 1.2.0 angeben.';

$GLOBALS['TL_LANG']['tl_app_common']['published'] = ['Veröffentlicht', 'In der App ausliefern. Nicht veröffentlichte Einträge sind für die App unsichtbar.'];
$GLOBALS['TL_LANG']['tl_app_common']['start'] = ['Anzeigen ab', 'Erst ab diesem Zeitpunkt ausliefern.'];
$GLOBALS['TL_LANG']['tl_app_common']['stop'] = ['Anzeigen bis', 'Nach diesem Zeitpunkt nicht mehr ausliefern.'];
$GLOBALS['TL_LANG']['tl_app_common']['minAppVersion'] = ['Mindestens App-Version', 'Nur Apps ab dieser Version (z. B. 1.2.0) bekommen den Eintrag. Leer = alle. Nötig für Bausteine, die erst eine neuere App versteht.'];
$GLOBALS['TL_LANG']['tl_app_common']['platform'] = ['Plattform', 'Nur für diese Plattform ausliefern. Leer = iOS und Android.'];

$GLOBALS['TL_LANG']['tl_app_common']['publish_legend'] = 'Sichtbarkeit';
