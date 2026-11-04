<?php

/*
 * Schalter der App (Backend > App > Schalter): erscheinen als flags.<Schlüssel> in /api/v1/app/config.
 * Wert "1"/"true"/"ja" und "0"/"false"/"nein" werden als Ja/Nein, Zahlen als Zahl, alles andere als Text ausgeliefert.
 */

use App\Dca\AppDca;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_app_flag'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'flagKey' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['flagKey'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['flagKey', 'flagValue', 'description'],
            'format' => '%s <span class="label-info">= %s</span> <span style="color:#999">%s</span>',
        ],
        'operations' => [
            'edit',
            'copy',
            'delete',
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
                'primary' => true,
            ],
            'show',
        ],
    ],

    'palettes' => [
        'default' => '{flag_legend},flagKey,flagValue,description;{publish_legend},published,start,stop,minAppVersion,platform',
    ],

    'fields' => [
        'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'flagKey' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'alias', 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'flagValue' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'description' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'clr'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
    ] + AppDca::visibilityFields(),
];
