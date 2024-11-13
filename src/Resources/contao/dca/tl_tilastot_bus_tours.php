<?php

/*
 * This file is part of the DelClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_bus_tours'] = array(
    // Config
    'config'   => array(
        'dataContainer' => 'Table',
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
            'fields'                  => array('tourdate'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array(
            'fields'                  => array('tourdate', 'hometeam'),
            'showColumns'             => true
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
            'edit' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_bus_tours']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy'   => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_bus_tours']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.gif',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_bus_tours']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'toggle' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_bus_tours']['toggle'],
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_bus_tours']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array(
        'default' => 'hometeam,tourdate,tourtime,price,minparticipants,full;published;notes'
    ),
    // Fields
    'fields'   => array(
        'id' => array(
            'sql'                     => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'hometeam' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['hometeam'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr w50'),
            'sql'                     => "varchar(50) NULL"
        ),
        'tourdate' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['gamedate'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('datepicker' => true, 'rgxp' => 'date', 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NULL"
        ),
        'tourtime' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['gametime'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(25) NOT NULL default ''"
        ),
        'price' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['price'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(50) NULL"
        ),
        'minparticipants' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['minparticipants'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('tl_class' => 'w50'),
            'sql'                     => "varchar(50) NULL"
        ),
        'notes' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['notes'],
            'exclude'                 => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr', 'mandatory' => false),
            'sql'                     => "blob Null"
        ),
        'full' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_bus_tours']['full'],
            'exclude'                 => true,
            'toggle'                  => true,
            'search'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class' => 'w50'),
            'sql'                     => "int(1) NOT NULL default '0'"
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
    )
);
