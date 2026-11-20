<?php

/*
 * Auth0-Frontend-Login: Rolle, die zu dieser Mitgliedergruppe gehoert.
 *
 * Beim Login setzt App\Auth0\Auth0MemberProvisioner die Gruppen des Mitglieds nach den Rollen
 * aus Auth0. Die Spalte legt App\Migration\Auth0MemberMigration an.
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('auth0_legend', 'redirect_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('auth0Role', 'auth0_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_member_group');

$GLOBALS['TL_DCA']['tl_member_group']['fields']['auth0Role'] = [
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'nospace' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];
