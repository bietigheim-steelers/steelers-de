<?php

/*
 * This file is part of the TilastotClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_client_stats'] = array
(
    // Config
    'config'   => array
    (
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'ptable' => 'tl_tilastot_client_players',
        'sql' => array
        (
            'keys' => array
            (
                'id' => 'primary',
				'pid,round' => 'unique'
            )
            ),
            'onload_callback' => [
                function () {
                    $db = Database::getInstance();
                    $pid = Input::get('pid');
                    if (empty($pid)) {
                        return;
                    }
                    $result = $db->prepare('SELECT `firstname`, `lastname` FROM `tl_tilastot_client_players` WHERE `id` = ?')
                                 ->execute([$pid]);
                    $prefix = strtoupper(substr($result->lastname, 0, 2));
                    $GLOBALS['TL_DCA']['tl_parts']['fields']['number']['default'] = $prefix;
                },
            ] 
    ),
    // List
    'list' => array
    (
        'sorting' => array
        (
            'mode'                    => DataContainer::MODE_PARENT,
            'fields' => ['round'],
            'headerFields' => ['firstname','lastname'],
            'panelLayout'             => 'filter;search,limit',
            'child_record_callback' => function (array $row) {
                return '<div class="tl_content_left">'.$row['games'].' Spiele, '.$row['points'].' Punkte ('.$row['goals'].' Tore, '.$row['assists'].' Vorlagen)</div>';
            },
        ),
        'label' => array
        (
            'fields'                  => array('round', 'games','points'),
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
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_stats']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy'   => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_stats']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.gif',
            ),
            'delete' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_stats']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array
            (
                'label'               => &$GLOBALS['TL_LANG']['tl_tilastot_client_stats']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array
    (
        'default' => 'jersey'
    ),
    // Fields
    'fields'   => array
    (
        'id' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'pid' => [
            'foreignKey' => 'tl_tilastot_client_players.id',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type'=>'belongsTo', 'load'=>'lazy']
        ],
        'tstamp' => array
        (
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'round' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_players']['round'],
            'exclude'                 => true,
            'filter'                  => true,
            'inputType'               => 'text',
            'options_callback'		  => array('App\\Tilastot\\Model\\Rounds', 'findForSelect'),
            'eval'                    => array('mandatory' => true, 'rgxp'=>'numeric', 'tl_class' => 'w50'),
            'sql'                     => "int(10) unsigned NOT NULL default '0'"
        ),
        'jersey' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['jersey'],
            'exclude'                 => true,
            'inputType'               => 'text',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'games' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['games'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'goals' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['goals'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'assists' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['assists'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'points' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['points'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'penalties' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['penalties'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'plusminus' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['plusminus'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'faceoffswon' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['faceoffswon'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'faceoffslost' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['faceoffslost'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        ),
        'shots' => array
        (
            'label'                   => &$GLOBALS['TL_LANG']['tl_tilastot_client_standings']['shots'],
            'exclude'                 => true,
            'inputType'               => 'number',
            'eval'                    => array('mandatory'=>true, 'maxlength'=>5, 'rgxp'=>'numeric', 'tl_class'=>'w50'),
            'sql'                     => "int(5) NOT NULL default '0'"
        )
    )
);