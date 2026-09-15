<?php

/*
 * Auktionsgebote
 *
 * Die Tabelle wird nicht im Backend gepflegt, sondern von den Auktionsformularen befüllt
 * (Formular → "Eingaben speichern" → Zieltabelle steelers_auktion). Die Formularfelder müssen
 * deshalb genauso heißen wie die Spalten. Ausgegeben werden die Höchstgebote über das
 * Frontend-Modul "auction" (App\Controller\FrontendModule\AuctionModule).
 *
 * Die Spaltendefinitionen entsprechen dem Bestand; Änderungen daran laufen über
 * App\Migration\AuctionMigration.
 */

use App\Dca\AuctionBidDca;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['steelers_auktion'] = [
    // Config
    'config' => [
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'notCreatable'     => true,
        'notCopyable'      => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'               => DataContainer::MODE_SORTABLE,
            'fields'             => ['player', 'gebot DESC'],
            'panelLayout'        => 'filter;sort,search,limit',
            'defaultSearchField' => 'nachname',
        ],
        'label' => [
            'fields'         => ['player', 'trikotsatz', 'gebot', 'vorname', 'nachname', 'email', 'tstamp'],
            'showColumns'    => true,
            'label_callback' => [AuctionBidDca::class, 'formatListLabel'],
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{bid_legend},player,trikotsatz,gebot;{contact_legend},vorname,nachname,anschrift,plz,ort,email,Telefonnummer,autogramm',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sorting' => true,
            'flag'    => DataContainer::SORT_DAY_DESC,
            'eval'    => ['rgxp' => 'datim'],
            'sql'     => "int(10) unsigned NOT NULL default 0",
        ],
        'player' => [
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DataContainer::SORT_ASC,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 250, 'tl_class' => 'w50'],
            'sql'       => "varchar(250) NOT NULL",
        ],
        // Optional: versteckte Formularfeld "trikotsatz", z. B. um Gebote einer bestimmten Auktion zuzuordnen
        'trikotsatz' => [
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'gebot' => [
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'rgxp' => 'digit', 'minval' => 0, 'tl_class' => 'w50'],
            'sql'       => "decimal(10,2) NOT NULL default '0.00'",
        ],
        'vorname' => [
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 50, 'tl_class' => 'w50'],
            'sql'       => "varchar(50) NOT NULL",
        ],
        'nachname' => [
            'search'    => true,
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['maxlength' => 50, 'tl_class' => 'w50'],
            'sql'       => "varchar(50) NOT NULL",
        ],
        'anschrift' => [
            'inputType' => 'text',
            'eval'      => ['maxlength' => 50, 'tl_class' => 'long clr'],
            'sql'       => "varchar(50) NOT NULL",
        ],
        'plz' => [
            'inputType' => 'text',
            'eval'      => ['maxlength' => 6, 'tl_class' => 'w50'],
            'sql'       => "varchar(6) NOT NULL",
        ],
        'ort' => [
            'inputType' => 'text',
            'eval'      => ['maxlength' => 100, 'tl_class' => 'w50'],
            'sql'       => "varchar(100) NOT NULL",
        ],
        'email' => [
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'email', 'maxlength' => 200, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(200) NOT NULL",
        ],
        'Telefonnummer' => [
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'phone', 'maxlength' => 50, 'decodeEntities' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(50) NOT NULL",
        ],
        'autogramm' => [
            'inputType' => 'text',
            'eval'      => ['maxlength' => 50, 'tl_class' => 'w50'],
            'sql'       => "varchar(50) DEFAULT NULL",
        ],
    ],
];
