<?php

use App\EventListener\AmountFormFieldListener;

// Eingabeprüfung "Betrag" (siehe App\EventListener\AmountFormFieldListener)
$GLOBALS['TL_DCA']['tl_form_field']['fields']['rgxp']['options'][] = AmountFormFieldListener::RGXP_NAME;

// Wie bei "digit" Mindest- und Höchstwert anbieten, aber ohne "step" (gilt nur für type="number")
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['text'.AmountFormFieldListener::RGXP_NAME] = str_replace(
    ',step',
    '',
    $GLOBALS['TL_DCA']['tl_form_field']['palettes']['textdigit']
);
