<?php

/*
 * Auth0-Frontend-Login: Verknuepfung des Mitglieds mit dem Auth0-Benutzer.
 *
 * Die Spalte legt App\Migration\Auth0MemberMigration an. Gefuellt wird sie beim ersten Login
 * ueber Auth0 (App\Auth0\Auth0MemberProvisioner); im Backend ist sie nur zur Kontrolle sichtbar.
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('auth0_legend', 'login_legend', PaletteManipulator::POSITION_AFTER)
    ->addField('auth0Sub', 'auth0_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member');

$GLOBALS['TL_DCA']['tl_member']['fields']['auth0Sub'] = [
    'inputType' => 'text',
    'eval'      => ['maxlength' => 255, 'readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];
