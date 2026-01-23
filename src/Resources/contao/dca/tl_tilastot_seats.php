<?php
/*
 * This file is part of the DelClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_seats'] = array(
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
            'fields'                  => array('seat_block', 'seat_row', 'seat_seat'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array(
            'fields'                  => array('seat_block', 'seat_row', 'seat_seat'),
            'format' => '<b>Block %s</b>, Reihe %s, Platz %s',
            'label_callback' => array('tl_tilastot_seats', 'seatLabel')
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
                'label'               => &$GLOBALS['TL_LANG']['tilastot_season_ticket']['edit'],
                'href'                => 'act=edit',
                'icon'                => 'edit.gif'
            ),
            'copy'   => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_season_ticket']['copy'],
                'href'                => 'act=paste&amp;mode=copy',
                'icon'                => 'copy.gif',
            ),
            'delete' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_season_ticket']['delete'],
                'href'                => 'act=delete',
                'icon'                => 'delete.gif',
                'attributes'          => 'onclick="if(!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_season_ticket']['show'],
                'href'                => 'act=show',
                'icon'                => 'show.gif'
            )
        )
    ),
    // Palettes
    'palettes' => array(
        'default' => 'seat_block,seat_row,seat_seat;seat_status;'
    ),
    // Fields
    'fields'   => array(
        'id' => array(
            'sql' => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array(
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'seat_block' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_seats']['seat_block'],
            'inputType' => 'select',
            'options' => array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'),
            'sql' => "varchar(4) NOT NULL default ''",
            'search' => true,
            'filter' => true,
            'sorting' => true

        ),
        'seat_row' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_seats']['seat_row'],
            'inputType' => 'text',
            'sql' => "varchar(8) NOT NULL default ''",
            'search' => true,
            'filter' => true,
            'sorting' => true
        ),
        'seat_seat' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_seats']['seat_seat'],
            'inputType' => 'text',
            'sql' => "varchar(8) NOT NULL default ''",
            'search' => true,
            'filter' => true,
            'sorting' => true
        ),
        'seat_status' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_seats']['seat_status'],
            'inputType' => 'select',
            'options' => array('available', 'reserved', 'booked', 'non-existent'),
            'sql' => "varchar(32) NOT NULL default 'available'",
            'search' => true,
            'filter' => true,
            'sorting' => true
        )
    )
);

class tl_tilastot_seats extends \Contao\Backend
{
    function seatLabel($row) {
        $ticket = sprintf('<b>Block %s</b>, Reihe %s, Platz %s - ',
            $row['seat_block'],
            $row['seat_row'],
            $row['seat_seat']
        );

        $status = '';
        switch ($row['seat_status']) {
            case 'available':
                $status = '<span style="color:green;">verfügbar</span>';
                break;
            case 'reserved':
                $status = '<span style="color:orange;">reserviert</span>';
                break;
            case 'booked':
                $status = '<span style="color:red;">verkauft</span>';
                break;
            case 'non-existent':
                $status = '<span style="color:gray;">nicht existent</span>';
                break;
        }

        return $ticket . $status;
    }
}
