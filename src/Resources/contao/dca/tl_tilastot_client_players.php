<?php

/*
 * This file is part of the TilastotClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_client_players'] = array(
    // Config
    'config'   => array(
        'dataContainer' => 'Table',
        'ctable' => ['tl_tilastot_client_stats'],
        'enableVersioning' => true,
        'sql' => array(
            'keys' => array(
                'id' => 'primary'
            )
        )
    ),
    // List
    'list' => array(
        'sorting' => array(
            'mode'                    => 11,
            'fields'                  => array('lastname'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array(
            'fields'                  => array('lastname', 'firstname'),
            'showColumns'             => true,
        ),
        'global_operations' => array(
            'all' => array(
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array(
            'edit' => [
                'href' => 'table=tl_tilastot_client_stats',
                'icon' => 'edit.svg',
            ],
            'editheader' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'header.gif'
            ),
            'copy'   => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.gif',
            ),
            'toggle' => array(
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array(
        'default' => 'lastname,firstname,eliteprospectsid,birthday,jersey,position,alias,steelersince,warmuppartner;about;{source_legend},pictures'
    ),
    // Fields
    'fields'   => array(
        'id' => array(
            'sql'                     => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'tilastotid' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['tilastotid'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'eliteprospectsid' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['eliteprospectsid'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 7, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(10) NOT NULL default '0'"
        ),
        'alias' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['alias'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'jersey' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['jersey'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'published' => array(
            'exclude'                 => true,
            'toggle'                  => true,
            'filter'                  => true,
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType'               => 'checkbox',
            'eval'                    => array('doNotCopy' => true),
            'sql'                     => "char(1) NOT NULL default ''"
        ),
        'firstname' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['firstname'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'lastname' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['lastname'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'position' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['position'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(5) NOT NULL default ''"
        ),
        'nationality' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['nationality'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'shoots' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['shoots'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(5) NOT NULL default ''"
        ),
        'birthplace' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['position'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'warmuppartner' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['warmuppartner'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => false, 'maxlength' => 255, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'birthday' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['birthday'],
            'inputType'               => 'text',
            'eval'                    => array('datepicker' => true, 'rgxp' => 'date', 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "int(25) NOT NULL default '0'"
        ),
        'height' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['height'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'weight' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['weight'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'steelersince' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['steelersince'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => false, 'maxlength' => 5, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(12) NULL"
        ),
        'about' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['about'],
            'exclude'                 => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte'=>'tinyMCE', 'basicEntities'=>true, 'tl_class'=>'clr', 'mandatory' => false),
            'sql'                     => "blob Null"
        ),
        'epstats' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['epstats'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "blob Null"
        ),
        'orderPictures' => array(
            'label'                   => &$GLOBALS['TL_LANG']['MSC']['sortOrder'],
            'sql'                     => "blob NULL"
        ),
        'pictures' => array(
            'label'                   => array('Spielerfotos', 'Erstes Bild wird als Portrait-Foto genutzt'),
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => array('multiple' => true, 'fieldType' => 'checkbox', 'orderField' => 'orderPictures', 'files' => true),
            'sql'                     => "blob NULL"
        )
    )
);