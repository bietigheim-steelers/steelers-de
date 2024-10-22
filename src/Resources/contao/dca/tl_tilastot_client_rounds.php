<?php

/*
 * This file is part of the TilastotBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Table tl_tilastot_client_rounds
 */
$GLOBALS['TL_DCA']['tl_tilastot_client_rounds'] = array
(
    // Config
    'config'   => array
    (
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary'
            )
        )
    ),
    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode'                    => 11,
            'fields'                  => array('year'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array
        (
            'fields'                  => array('year', 'name', 'season'),
            'showColumns'             => true,
        ),
        'global_operations' => array
        (
            'all' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'                => 'act=select',
                'class'               => 'header_edit_all',
                'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array
        (
            'edit' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'delete' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.svg' 
            )
        )
    ),
    // Palettes
    'palettes' => array
    (
        'default' => 'year,league,standingsid,name,season,autorefresh;api,apikey'
    ),
    // Fields
    'fields'   => array
    (
        'id' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'standingsid' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['standingsid'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'api' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['api'],
            'exclude'                 => true,
            'inputType'               => 'select',
            'options'                 => array('holema' =>'DEL2 - Holema', 'del' =>'DEL', 'deb' => 'Oberliga - DEB'),
            'eval'                    => array('rgxp' => 'alpha', 'mandatory' => false, 'maxlength' => 6, 'tl_class' => 'w50'),
            'sql'                     => "varchar(10) NULL"
        ),
        'apikey' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['api'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NULL"
        ),
        'year' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['year'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>false, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'league' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['league'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>false, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'name' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['name'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'season' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['season'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>255, 'rgxp'=>'alphanumeric', 'tl_class'=>'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'autorefresh' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_rounds']['autorefresh'],
            'exclude'                 => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('mandatory'=>false, 'tl_class'=>'w50'),
            'sql'                     => "blob NULL"
        )
    )
);
