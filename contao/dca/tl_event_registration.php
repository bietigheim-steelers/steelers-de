<?php

use Contao\DC_Table;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_event_registration'] = [

    // Config
    'config' => [
        'dataContainer'    => DC_Table::class,
        'ptable'           => 'tl_calendar_events',
        'closed'           => true,
        'notCopyable'      => true,
        'notCreatable'     => true,
        'sql'              => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'                => DataContainer::MODE_PARENT,
            'fields'              => ['dateAdded DESC'],
            'headerFields'        => ['title', 'startDate', 'regEnabled'],
            'panelLayout'         => 'search,limit',
            'defaultSearchField'  => 'submitterEmail',
        ],
        'label' => [
            'fields'         => ['submitterName', 'submitterEmail', 'dateAdded'],
            'format'         => '%s &lt;%s&gt; – %s',
            'label_callback' => ['App\\EventListener\\EventRegistrationListener', 'listRegistration'],
        ],
        'operations' => [
            'show',
            'delete',
        ],
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_calendar_events.title',
            'sql'        => 'int(10) unsigned NOT NULL default 0',
            'relation'   => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp' => [
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'dateAdded' => [
            'filter'  => true,
            'sorting' => true,
            'flag'    => DataContainer::SORT_MONTH_BOTH,
            'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'     => 'int(10) unsigned NOT NULL default 0',
        ],
        'submitterName' => [
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['readonly' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'submitterEmail' => [
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['readonly' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'submitterPhone' => [
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['readonly' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'data' => [
            'inputType' => 'textarea',
            'eval'      => ['readonly' => true, 'tl_class' => 'clr'],
            'sql'       => 'mediumtext NULL',
        ],
        'ip' => [
            'search'    => true,
            'inputType' => 'text',
            'eval'      => ['readonly' => true, 'tl_class' => 'w50'],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
    ],
];
