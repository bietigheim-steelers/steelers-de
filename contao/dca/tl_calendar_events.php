<?php

use Contao\DataContainer;

// Register tl_event_registration as child table of tl_calendar_events
$GLOBALS['TL_DCA']['tl_calendar_events']['config']['ctable'][] = 'tl_event_registration';

// Add all registration fields directly into reg_legend (no sub-palette/submitOnChange needed)
foreach (['default', 'internal', 'article', 'external'] as $palette) {
    if (isset($GLOBALS['TL_DCA']['tl_calendar_events']['palettes'][$palette])) {
        $GLOBALS['TL_DCA']['tl_calendar_events']['palettes'][$palette] .= ';{reg_legend:hide},regEnabled,regFormType,regFormId,regCustomFields,regToken,regDeadline,regMaxParticipants,regNotificationEmail';
    }
}

// Add "Registrations" operation button in the event list
$GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations']['registrations'] = [
    'href'  => 'table=tl_event_registration',
    'icon'  => 'modules.svg',
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['registrations'],
];

// -------------------------------------------------------------------------
// Fields
// -------------------------------------------------------------------------

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regEnabled'] = [
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regFormType'] = [
    'inputType' => 'select',
    'options'   => ['custom', 'existing'],
    'reference' => &$GLOBALS['TL_LANG']['tl_calendar_events']['regFormType_options'],
    'eval'      => ['mandatory' => true, 'tl_class' => 'w50 clr'],
    'sql'       => "varchar(16) NOT NULL default 'custom'",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regFormId'] = [
    'inputType'  => 'select',
    'foreignKey' => 'tl_form.title',
    'eval'       => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql'        => 'int(10) unsigned NOT NULL default 0',
    'relation'   => ['type' => 'hasOne', 'load' => 'lazy'],
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regCustomFields'] = [
    'inputType' => 'multiColumnWizard',
    'eval'      => [
        'columnFields' => [
            'fieldType' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['regCustomFields_type'],
                'inputType' => 'select',
                'options'   => ['text', 'email', 'tel', 'number', 'textarea', 'select', 'checkbox'],
                'eval'      => ['style' => 'width:110px'],
            ],
            'fieldLabel' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['regCustomFields_label'],
                'inputType' => 'text',
                'eval'      => ['style' => 'width:180px'],
            ],
            'fieldName' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['regCustomFields_name'],
                'inputType' => 'text',
                'eval'      => ['style' => 'width:130px'],
            ],
            'mandatory' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['regCustomFields_mandatory'],
                'inputType' => 'checkbox',
                'eval'      => ['style' => 'width:60px'],
            ],
            'fieldOptions' => [
                'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['regCustomFields_options'],
                'inputType' => 'text',
                'eval'      => ['style' => 'width:180px'],
            ],
        ],
        'tl_class' => 'clr',
    ],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regToken'] = [
    'inputType'      => 'text',
    'eval'           => ['readonly' => true, 'doNotCopy' => true, 'tl_class' => 'w50'],
    'save_callback'  => [
        ['App\\EventListener\\EventRegistrationListener', 'generateToken'],
    ],
    'sql'            => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regDeadline'] = [
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
    'sql'       => 'bigint(20) NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regMaxParticipants'] = [
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'digit', 'tl_class' => 'w50'],
    'sql'       => 'int(10) unsigned NULL',
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['regNotificationEmail'] = [
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'emails', 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''",
];
