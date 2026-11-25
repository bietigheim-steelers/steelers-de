<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['palettes']['player'] =
  '{title_legend},title,alias,type;{publish_legend},published,start,stop';

$GLOBALS['TL_DCA']['tl_page']['fields']['forwardQuery'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

// Seitentyp "Weiterleitung" (forward) gibt die Query-Parameter bereits von Haus aus weiter,
// nur der Typ "Umleitung" (redirect) nicht.
PaletteManipulator::create()
    ->addField('forwardQuery', 'url', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('redirect', 'tl_page');
