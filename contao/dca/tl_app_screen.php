<?php

/*
 * Screens, Tabs und Menüeinträge der App (Backend > App > Screens).
 * Die Bausteine eines Screens stehen in tl_app_block. Ausgeliefert wird das über /api/v1/app/*,
 * siehe docs/openapi.yaml.
 */

use App\Dca\AppDca;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_app_screen'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_app_block'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'screenKey' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['sortOrder'],
            'flag' => DataContainer::SORT_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title'],
            'label_callback' => [AppDca::class, 'screenLabel'],
        ],
        'operations' => [
            'edit',
            'children',
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
        '__selector__' => ['kind'],
        'default' => '{screen_legend},title,screenKey,kind,placement,icon,sortOrder,isHome;{publish_legend},published,start,stop,minAppVersion,platform',
        'link' => '{screen_legend},title,screenKey,kind,url,placement,icon,sortOrder;{publish_legend},published,start,stop,minAppVersion,platform',
        'native' => '{screen_legend},title,screenKey,kind,placement,icon,sortOrder;{publish_legend},published,start,stop,minAppVersion,platform',
    ],

    'fields' => [
        'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'sortOrder' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'maxlength' => 5, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'screenKey' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'unique' => true, 'rgxp' => 'alias', 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'title' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'icon' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'placement' => [
            'filter' => true,
            'inputType' => 'select',
            'options' => ['tab', 'menu', 'hidden'],
            'reference' => &$GLOBALS['TL_LANG']['tl_app_screen']['placements'],
            'eval' => ['maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'tab'",
        ],
        'kind' => [
            'filter' => true,
            'inputType' => 'select',
            'options' => ['screen', 'native', 'link'],
            'reference' => &$GLOBALS['TL_LANG']['tl_app_screen']['kinds'],
            'eval' => ['submitOnChange' => true, 'maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'screen'",
        ],
        'url' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'tl_class' => 'w50'],
            'sql' => "varchar(2048) NOT NULL default ''",
        ],
        'isHome' => [
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ] + AppDca::visibilityFields(),
];
