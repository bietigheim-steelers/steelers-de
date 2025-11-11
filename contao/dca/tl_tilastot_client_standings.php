<?php

/*
 * This file is part of the HolemaClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_client_standings'] = array(
    // Config
    'config'   => array(
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'sql' => array(
            'keys' => array(
                'id' => 'primary',
                'tilastotid,round' => 'unique'
            )
        )
    ),
    // List
    'list' => array(
        'sorting' => array(
            'mode'                    => 11,
            'fields'                  => array('round'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array(
            'fields'                  => array('round', 'name', 'shortname', 'city'),
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
            'edit' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy'   => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.gif',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array(
        'default' => 'round,name,tilastotid,alias,shortname,city;{logo_legend},logo'
    ),
    // Fields
    'fields'   => array(
        'id' => array(
            'sql'                     => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array(
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'round' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['round'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'select',
            'options_callback'          => array('App\\Tilastot\\Model\\Rounds', 'findForSelect'),
            'eval'                    => array('mandatory' => true, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'name' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['team_name'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'tilastotid' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['tilastotid'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'alias' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['team_alias'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'shortname' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['team_shortname'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'city' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['team_city'],
            'exclude'                 => true,
            'search'                  => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory' => true, 'maxlength' => 255, 'rgxp' => 'alphanumeric', 'tl_class' => 'w50'),
            'sql'                     => "varchar(255) NOT NULL default ''"
        ),
        'games' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['games'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'rw' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['rw'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'ow' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['ow'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'pw' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['pw'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'pl' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['pl'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'ol' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['ol'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'rl' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['rl'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'points' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['points'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'goalsfor' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['goalsfor'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'goalsagainst' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['goalsagainst'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'penalties' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['penalties'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'logo' => array(
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['logo'],
            'exclude'                 => true,
            'inputType'               => 'fileTree',
            'eval'                    => array('fieldType' => 'radio', 'filesOnly' => true, 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => false),
            'sql'                     => "binary(16) NULL"
        ),
    )
);