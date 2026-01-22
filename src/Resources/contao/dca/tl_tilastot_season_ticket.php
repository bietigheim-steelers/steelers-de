<?php

use App\Tilastot\Model\Standings;

/*
 * This file is part of the DelClientBundle.
 *
 * (c) Dominik Sander <http://dominix-design.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$GLOBALS['TL_DCA']['tl_tilastot_season_ticket'] = array(
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
            'flag'                    => DataContainer::SORT_MONTH_DESC,
            'fields'                  => array('customer_name'),
            'panelLayout'             => 'filter;search,limit'
        ),
        'label' => array(
            'fields'                  => array('customer_name', 'customer_firstname', 'customer_email'),
            'format' => '%s, %s <span style="color:#999;padding-left:3px">[%s]</span>'
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
            'toggle' => array(
                'label'               => &$GLOBALS['TL_LANG']['tilastot_season_ticket']['toggle'],
                'href'                => 'act=toggle&amp;field=published',
                'icon'                => 'visible.svg',
                'showInHeader'        => true
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
        'default' => '{ticket_legend},ticket_type,ticket_area,ticket_category,seat_block,seat_row,seat_seat,ticket_form,ticket_payment;{customer_legend},customer_firstname,customer_name,customer_street,customer_plz,customer_city,customer_phone,customer_email,customer_birthday,customer_last_season;{eventim_legend},eventim,eventim_email,eventim_account;bemerkung,terms,data_privacy,price,order_number,payed,pay_date'
    ),
    // Fields
    'fields'   => array(
        'id' => array(
            'sql' => "int(10) unsigned NOT NULL AUTO_INCREMENT"
        ),
        'tstamp' => array(
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'ticket_type' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['ticket_type'],
            'inputType' => 'select',
            'options' => array('basic', 'plus'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'ticket_area' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['ticket_area'],
            'inputType' => 'select',
            'options' => array('sitzplatz', 'stehplatz', 'rollstuhl'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'ticket_category' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['ticket_category'],
            'inputType' => 'select',
            'options' => array('vollzahler', 'rentner', 'student', 'azubi', 'schueler', 'mitglied', 'jugendlich', 'kind', 'behinderung', 'familie1', 'familie2', 'familie3'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'seat_block' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['seat_block'],
            'inputType' => 'select',
            'options' => array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'),
            'sql' => "varchar(8) NOT NULL default ''"
        ),
        'seat_row' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['seat_row'],
            'inputType' => 'text',
            'sql' => "varchar(8) NOT NULL default ''"
        ),
        'seat_seat' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['seat_seat'],
            'inputType' => 'text',
            'sql' => "varchar(8) NOT NULL default ''"
        ),
        'ticket_form' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['ticket_form'],
            'inputType' => 'select',
            'options' => array('mobile_plastik', 'mobile', 'plastik'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'ticket_payment' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['ticket_payment'],
            'inputType' => 'select',
            'options' => array('ueberweisung', 'gs'),
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'customer_firstname' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_firstname'],
            'inputType' => 'text',
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'customer_name' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_name'],
            'inputType' => 'text',
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'customer_street' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_street'],
            'inputType' => 'text',
            'sql' => "varchar(128) NOT NULL default ''"
        ),
        'customer_plz' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_plz'],
            'inputType' => 'text',
            'sql' => "varchar(16) NOT NULL default ''"
        ),
        'customer_city' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_city'],
            'inputType' => 'text',
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'customer_phone' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_phone'],
            'inputType' => 'text',
            'sql' => "varchar(32) NOT NULL default ''"
        ),
        'customer_email' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_email'],
            'inputType' => 'text',
            'sql' => "varchar(128) NOT NULL default ''"
        ),
        'customer_birthday' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_birthday'],
            'inputType' => 'text',
            'sql' => "date NULL"
        ),
        'customer_last_season' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['customer_last_season'],
            'inputType' => 'checkbox',
            'sql' => "char(2) NOT NULL default ''"
        ),
        'eventim' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['eventim'],
            'inputType' => 'checkbox',
            'sql' => "char(2) NOT NULL default ''"
        ),
        'eventim_email' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['eventim_email'],
            'inputType' => 'text',
            'sql' => "varchar(128) NOT NULL default ''"
        ),
        'eventim_account' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['eventim_account'],
            'inputType' => 'text',
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'bemerkung' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['bemerkung'],
            'inputType' => 'textarea',
            'sql' => "text NULL"
        ),
        'terms' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['terms'],
            'inputType' => 'checkbox',
            'sql' => "char(4) NOT NULL default ''"
        ),
        'data_privacy' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['data_privacy'],
            'inputType' => 'checkbox',
            'sql' => "char(4) NOT NULL default ''"
        ),
        'price' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['price'],
            'inputType' => 'text',
            'sql' => "decimal(10,2) NOT NULL default '0.00'"
        ),
        'order_number' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['order_number'],
            'inputType' => 'text',
            'sql' => "int(11) NOT NULL default '0'"
        ),
        'paid' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['paid'],
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
            'sql' => "char(1) NOT NULL default '0'"
        ),
        'pay_date' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_tilastot_season_ticket']['pay_date'],
            'inputType' => 'text',
            'eval' => array('datepicker' => true, 'rgxp' => 'date', 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "int(25) NOT NULL default '0'"
        ),
    )
);

class tl_tilastot_season_ticket extends Backend
{
    public function loadDate($value)
    {
        return strtotime(date('Y-m-d', $value) . ' 00:00:00');
    }

    public function showTour($row, $label, $dc, $args)
    {
        $args[1] = date('d.m.Y', $args[1]);
        return $args;
    }
}
