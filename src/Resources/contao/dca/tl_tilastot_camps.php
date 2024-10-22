<?php

/*
 * This file is part of the DelClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_camps'] = array(
    // Config
    'config'   => array(
        'dataContainer' => 'Table',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'markAsCopy' => true,
        'sql' => array(
            'keys' => array(
                'id' => 'primary'
            )
        )

    ),
    // List
    'list' => array(
        'sorting' => array(
            'mode'                    => DataContainer::MODE_SORTED,
            'flag'                    => DataContainer::SORT_YEAR_DESC,
            'fields'                  => array('startdate'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array(
            'fields'                  => array('name', 'category'),
            'format' => '%s <span style="color:#999;padding-left:3px">[%s]</span>'
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
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy'   => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['copy'],
                'href'                => 'act=copy',
                'icon'                => 'copy.gif',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'toggle' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['toggle'],
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array(
        'default' => 'category,name,startdate,enddate,full;description;published'
    ),
    // Fields
    'fields'   => array(
        'id' => array(
            'sql'                     => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'name' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['name'],
            'exclude'                 => true,
            'search'                  => true,
            'filter'                  => true,
            'sorting'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'category' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['category'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => false, 'maxlength' => 255, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'startdate' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['startdate'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('datepicker' => true, 'rgxp' => 'date', 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NULL"
        ),
        'enddate' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['enddate'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('datepicker' => true, 'rgxp' => 'date', 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NULL"
        ),
        'description' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['description'],
            'exclude'                 => true,
            'inputType'               => 'textarea',
            'eval'                    => array('rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr', 'mandatory' => false),
            'sql'                     => "blob Null"
        ),
        'full' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['full'],
            'exclude'                 => true,
            'toggle'                  => true,
            'search'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class' => 'w50'),
            'sql'                     => "int(1) NOT NULL default '0'"
        ),
        'published' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_camps']['published'],
            'exclude'                 => true,
            'toggle'                  => true,
            'search'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class' => 'w50'),
            'sql'                     => "int(1) NOT NULL default '0'"
        )
    )
);
