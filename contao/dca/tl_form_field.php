<?php

use App\EventListener\AmountFormFieldListener;
use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Eingabeprüfung "Betrag" (siehe App\EventListener\AmountFormFieldListener)
$GLOBALS['TL_DCA']['tl_form_field']['fields']['rgxp']['options'][] = AmountFormFieldListener::RGXP_NAME;

// Wie bei "digit" Mindest- und Höchstwert anbieten, aber ohne "step" (gilt nur für type="number")
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['text'.AmountFormFieldListener::RGXP_NAME] = str_replace(
    ',step',
    '',
    $GLOBALS['TL_DCA']['tl_form_field']['palettes']['textdigit']
);

// --- Schrittassistent (Template form_wrapper_steps, siehe AGENTS.md) ----------------------
// Alle Felder sind optional. Leer gelten Standardtexte bzw. das Verhalten wie bisher.
// Spalten: App\Migration\FormStepsMigration.
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfStepLead'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'clr long'],
    'sql' => "varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfMsgRequired'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfMsgInvalid'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfSumGroup'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'rgxp' => 'alnum', 'tl_class' => 'w25 clr'],
    'sql' => "varchar(64) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfSumMin'] = [
    'inputType' => 'text',
    'eval' => ['rgxp' => 'natural', 'tl_class' => 'w25'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfSumUnit'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 32, 'decodeEntities' => true, 'tl_class' => 'w25'],
    'sql' => "varchar(32) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_form_field']['fields']['sfSumMessage'] = [
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'clr long'],
    'sql' => "varchar(255) NOT NULL default ''",
];

// Schritt-Einleitung am Fieldset
PaletteManipulator::create()
    ->addLegend('sf_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField('sfStepLead', 'sf_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('fieldsetStart', 'tl_form_field');

// Meldungen an allen Eingabefeldern
foreach (['text', 'textdigit', 'text'.AmountFormFieldListener::RGXP_NAME, 'textcustom', 'textarea', 'textareacustom', 'select', 'radio', 'checkbox'] as $palette) {
    PaletteManipulator::create()
        ->addLegend('sf_legend', 'expert_legend', PaletteManipulator::POSITION_BEFORE)
        ->addField(['sfMsgRequired', 'sfMsgInvalid'], 'sf_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette($palette, 'tl_form_field');
}

// Summenregel über mehrere Zahlenfelder
foreach (['textdigit', 'text'.AmountFormFieldListener::RGXP_NAME] as $palette) {
    PaletteManipulator::create()
        ->addField(['sfSumGroup', 'sfSumMin', 'sfSumUnit', 'sfSumMessage'], 'sf_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette($palette, 'tl_form_field');
}
