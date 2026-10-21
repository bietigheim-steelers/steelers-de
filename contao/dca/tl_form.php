<?php

/*
 * Haengt dem Formular die CleverReach-Anbindung an.
 *
 * Sind Gruppe und Formular gefuellt, traegt App\EventListener\CleverReachFormListener
 * die Anmeldung ueber die CleverReach-REST-API ein. Die Spalten legt
 * App\Migration\CleverReachFormMigration an (contao:migrate ist auf diesem
 * Datenbestand nicht benutzbar, siehe AGENTS.md).
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('cleverreach_legend', 'store_legend', PaletteManipulator::POSITION_BEFORE, true)
    ->addField(['cleverreachGroupId', 'cleverreachFormId'], 'cleverreach_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_form');

$GLOBALS['TL_DCA']['tl_form']['fields']['cleverreachGroupId'] = [
    'inputType' => 'text',
    'eval'      => [
        'rgxp'      => 'digit',
        'maxlength' => 20,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(20) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form']['fields']['cleverreachFormId'] = [
    'inputType' => 'text',
    'eval'      => [
        'rgxp'      => 'digit',
        'maxlength' => 20,
        'tl_class'  => 'w50',
    ],
    'sql'       => "varchar(20) NOT NULL default ''",
];
