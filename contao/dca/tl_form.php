<?php

/*
 * Haengt dem Formular die CleverReach-Flow-URL an.
 *
 * Ist sie gefuellt, reicht App\EventListener\CleverReachFormListener die Anmeldung
 * serverseitig dorthin weiter. Die Spalte legt App\Migration\CleverReachFormMigration
 * an (contao:migrate ist auf diesem Datenbestand nicht benutzbar, siehe AGENTS.md).
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('cleverreach_legend', 'store_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField('cleverreachUrl', 'cleverreach_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_form');

$GLOBALS['TL_DCA']['tl_form']['fields']['cleverreachUrl'] = [
    'inputType' => 'text',
    'eval'      => [
        'rgxp'           => 'url',
        'decodeEntities' => true,
        'maxlength'      => 255,
        'tl_class'       => 'long',
    ],
    'sql'       => "varchar(255) NOT NULL default ''",
];
