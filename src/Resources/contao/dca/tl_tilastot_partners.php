<?php

/*
 * This file is part of the DelClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_partners'] = array(
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
            'mode'                    => DataContainer::MODE_SORTED,
            'flag'                    => DataContainer::SORT_INITIAL_LETTER_ASC,
            'fields'                  => array('name'),
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
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy'   => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.gif',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'toggle' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['toggle'],
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array(
        'default' => 'name,displayname,url;category;logo;published'
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
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['name'],
            'exclude'                 => true,
            'search'                  => true,
            'filter'                  => true,
            'sorting'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'displayname' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['displayname'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'url' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['url'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('rgxp' => 'url', 'mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'category' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['category'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'checkbox',
            'options'                 => array(
                'premium' => 'Premiumpartner',
                'gold' => 'Businesspartner Gold',
                'silber' => 'Businesspartner Silber',
                'bronze' => 'Businesspartner Bronze',
                'business' => 'Businesspartner',
                'lounge' => 'Businesslounge',
                'medien' => 'Medienpartner',
                'carpool' => 'Carpool Partner',
                'team' => 'Teampartner',
                'supporter' => 'Supporter',
                'nachwuchs_haupt' => 'Nachwuchs - Hauptsponsor',
                'nachwuchs' => 'Nachwuchssponsor',
                'nachwuchsspieler' => 'Nachwuchs Spielerpatenschaften',
            ),
            'eval'                    => array('multiple' => true, 'mandatory' => true, 'maxlength' => 255, 'tl_class' => 'clr w50'),
            'sql'                     => "varchar(255) NULL"
        ),
        'logo' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['logo'],
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => false),
            'sql'                     => "binary(16) NULL"
        ),
        'published' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_partners']['published'],
            'exclude'                 => true,
            'toggle'                  => true,
            'search'                  => true,
            'inputType'               => 'checkbox',
            'eval'                    => array('tl_class' => 'w50'),
            'sql'                     => "int(1) NOT NULL default '0'"
        )
    )
);
