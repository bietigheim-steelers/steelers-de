<?php

/*
 * Bausteine eines App-Screens (Backend > App > Screens > Bausteine).
 *
 * Inhaltsbausteine (headline … promo) bringen ihren Inhalt mit, Datenbausteine (news_list … partners)
 * enthalten nur Konfiguration (die App lädt die Daten selbst), article/content_element binden Inhalte
 * der Webseite ein, die der Server in einfache Bausteine auflöst. Vertrag: docs/openapi.yaml (Block).
 */

use App\Dca\AppDca;
use App\Model\Partners;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_app_block'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_app_screen',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting'],
            'headerFields' => ['title', 'screenKey', 'placement', 'kind'],
            'panelLayout' => 'filter;search,limit',
            'child_record_callback' => [AppDca::class, 'blockRecord'],
        ],
        'operations' => [
            'edit',
            'copy',
            'cut',
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
        '__selector__' => ['blockType'],
        'default' => '{type_legend},blockType;{publish_legend},published,start,stop,minAppVersion,platform',
    ],

    'subpalettes' => [
        'blockType_headline' => 'blockTitle,blockLevel',
        'blockType_text' => 'blockText',
        'blockType_image' => 'blockImage,blockAlt,blockCaption,blockUrl',
        'blockType_list' => 'blockTitle,blockItems,blockOrdered',
        'blockType_button' => 'blockLabel,blockUrl',
        'blockType_download' => 'blockLabel,blockFile',
        'blockType_video' => 'blockTitle,blockProvider,blockVideoId',
        'blockType_promo' => 'blockTitle,blockPlain,blockImage,blockLabel,blockUrl',
        'blockType_news_list' => 'blockTitle,blockLimit,blockCategory,blockLayout',
        'blockType_next_game' => 'blockTitle',
        'blockType_games' => 'blockTitle,blockStatus,blockLimit,blockLayout',
        'blockType_standings' => 'blockTitle,blockCompact',
        'blockType_roster' => 'blockTitle,blockPosition',
        'blockType_partners' => 'blockTitle,blockPartnerCategory,blockLayout',
        'blockType_article' => 'blockArticle',
        'blockType_content_element' => 'blockContent',
    ],

    'fields' => [
        'id' => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'pid' => [
            'foreignKey' => 'tl_app_screen.title',
            'sql' => 'int(10) unsigned NOT NULL default 0',
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'sorting' => ['sql' => 'int(10) unsigned NOT NULL default 0'],
        'tstamp' => ['sql' => 'int(10) unsigned NOT NULL default 0'],

        'blockType' => [
            'filter' => true,
            'inputType' => 'select',
            'options' => [
                'headline', 'text', 'image', 'list', 'button', 'download', 'video', 'promo',
                'news_list', 'next_game', 'games', 'standings', 'roster', 'partners',
                'article', 'content_element',
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_app_block']['types'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'submitOnChange' => true, 'maxlength' => 32, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'blockTitle' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'blockText' => [
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'sql' => 'mediumtext NULL',
        ],
        'blockPlain' => [
            'inputType' => 'textarea',
            'eval' => ['rows' => 4, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'blockLevel' => [
            'inputType' => 'select',
            'options' => ['h1', 'h2', 'h3', 'h4'],
            'eval' => ['maxlength' => 2, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default 'h2'",
        ],
        'blockImage' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => true, 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'blockFile' => [
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'mandatory' => true, 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'blockAlt' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'blockCaption' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'blockUrl' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'dcaPicker' => true, 'maxlength' => 2048, 'tl_class' => 'clr wizard'],
            'sql' => "varchar(2048) NOT NULL default ''",
        ],
        'blockLabel' => [
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'blockItems' => [
            'inputType' => 'textarea',
            'eval' => ['mandatory' => true, 'rows' => 6, 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'blockOrdered' => [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'blockProvider' => [
            'inputType' => 'select',
            'options' => ['youtube', 'vimeo'],
            'eval' => ['maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'youtube'",
        ],
        'blockVideoId' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'blockLimit' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'maxlength' => 3, 'tl_class' => 'w50'],
            'sql' => "smallint(5) unsigned NOT NULL default 0",
        ],
        'blockCategory' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'blockPartnerCategory' => [
            'inputType' => 'select',
            'options_callback' => [Partners::class, 'getCategoryOptions'],
            'eval' => ['includeBlankOption' => true, 'maxlength' => 32, 'tl_class' => 'w50'],
            'sql' => "varchar(32) NOT NULL default ''",
        ],
        'blockLayout' => [
            'inputType' => 'select',
            'options' => ['list', 'carousel', 'grid', 'slider'],
            'reference' => &$GLOBALS['TL_LANG']['tl_app_block']['layouts'],
            'eval' => ['includeBlankOption' => true, 'maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'blockStatus' => [
            'inputType' => 'select',
            'options' => ['upcoming', 'finished'],
            'reference' => &$GLOBALS['TL_LANG']['tl_app_block']['statuses'],
            'eval' => ['maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default 'upcoming'",
        ],
        'blockCompact' => [
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'blockPosition' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 16, 'tl_class' => 'w50'],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'blockArticle' => [
            'inputType' => 'select',
            'options_callback' => [AppDca::class, 'articleOptions'],
            'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'clr'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'blockContent' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'maxlength' => 10, 'tl_class' => 'w50'],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
    ] + AppDca::visibilityFields(),
];
