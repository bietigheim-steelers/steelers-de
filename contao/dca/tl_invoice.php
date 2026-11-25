<?php

/*
 * Rechnungen der Gruppenbestellungen.
 *
 * Die Datensätze entstehen automatisch beim Absenden des Formulars (GroupOrderInvoiceListener).
 * Bearbeitet und versendet wird auf der eigenen Rechnungsseite (InvoiceController, Route
 * backend_invoice_edit), nicht über die Contao-Standardmaske – deshalb zeigt "edit" dorthin
 * und es gibt keine Palette. Die Tabelle wird per App\Migration\InvoiceMigration angelegt.
 */

use App\Dca\InvoiceDca;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_invoice'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'notCreatable'  => true,
        'notCopyable'   => true,
        'sql' => [
            'keys' => [
                'id'             => 'primary',
                'tstamp'         => 'index',
                'invoice_number' => 'unique',
            ],
        ],
    ],

    'list' => [
        'sorting' => [
            'mode'        => DataContainer::MODE_SORTED,
            'fields'      => ['created DESC'],
            'flag'        => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'search,filter,limit',
            'defaultSearchField' => 'recipient',
        ],
        'label' => [
            'fields'         => ['invoice_number', 'created', 'recipient', 'items', 'status'],
            'showColumns'    => true,
            'label_callback' => [InvoiceDca::class, 'formatLabel'],
        ],
        'operations' => [
            'edit' => [
                'route' => 'backend_invoice_edit',
                'icon'  => 'edit.svg',
            ],
            'delete' => [
                'href'            => 'act=delete',
                'icon'            => 'delete.svg',
                'button_callback' => [InvoiceDca::class, 'deleteButton'],
                'attributes'      => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    'fields' => [
        'id'      => ['sql' => 'int(10) unsigned NOT NULL auto_increment'],
        'tstamp'  => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'created' => ['sorting' => true, 'flag' => DataContainer::SORT_DAY_DESC, 'eval' => ['rgxp' => 'datim'], 'sql' => "int(10) unsigned NOT NULL default 0"],
        'form_id' => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'status'  => [
            'filter'    => true,
            'inputType' => 'select',
            'options'   => ['draft', 'sent'],
            'reference' => &$GLOBALS['TL_LANG']['tl_invoice']['status_options'],
            'sql'       => "varchar(8) NOT NULL default 'draft'",
        ],
        'invoice_number' => ['search' => true, 'sql' => 'varchar(32) NULL'],
        'invoice_date'   => ['eval' => ['rgxp' => 'date'], 'sql' => "int(10) unsigned NOT NULL default 0"],
        'recipient'      => ['search' => true, 'sql' => 'text NULL'],
        'subject'        => ['search' => true, 'sql' => "varchar(255) NOT NULL default ''"],
        'intro_text'     => ['sql' => 'text NULL'],
        'outro_text'     => ['sql' => 'text NULL'],
        'vat_rate'       => ['sql' => "decimal(5,2) NOT NULL default '19.00'"],
        'items'          => ['sql' => 'mediumtext NULL'],
        'customer_email' => ['search' => true, 'sql' => "varchar(255) NOT NULL default ''"],
        'send_to'        => ['sql' => "varchar(255) NOT NULL default ''"],
        'order_data'     => ['sql' => 'mediumtext NULL'],
        'sent_at'        => ['eval' => ['rgxp' => 'datim'], 'sql' => "int(10) unsigned NOT NULL default 0"],
        'sent_to'        => ['sql' => "varchar(255) NOT NULL default ''"],
        'sent_by'        => ['sql' => "varchar(255) NOT NULL default ''"],
        'sent_count'     => ['sql' => "int(10) unsigned NOT NULL default 0"],
        'modified_after_sent' => ['eval' => ['rgxp' => 'datim'], 'sql' => "int(10) unsigned NOT NULL default 0"],
    ],
];
