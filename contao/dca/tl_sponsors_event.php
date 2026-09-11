<?php

/*
 * Sponsors Events DCA
 * Verwaltet Sponsor-Events, die intern als normale Contao-Events gespeichert werden.
 */

use App\Dca\SponsorsEventDca;
use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_sponsors_event'] = [
    // Config
    'config' => [
        'dataContainer'    => DC_Table::class,
        'switchToEdit'     => true,
        'enableVersioning' => true,
        'markAsCopy'       => 'title',
        'onsubmit_callback' => [
            [SponsorsEventDca::class, 'onSubmit'],
        ],
        'sql' => [
            'keys' => [
                'id'           => 'primary',
                'tstamp'       => 'index',
                'access_token' => 'unique',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode'               => DataContainer::MODE_SORTED,
            'fields'             => ['startDate DESC'],
            'flag'               => DataContainer::SORT_DAY_DESC,
            'panelLayout'        => 'search,filter,limit',
            'defaultSearchField' => 'title',
        ],
        'label' => [
            'fields' => ['title', 'startDate'],
            'format' => '%s <span style="color:#999;padding-left:3px">[%s]</span>',
            'label_callback' => [SponsorsEventDca::class, 'formatListLabel'],
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
            ],
            'toggle' => [
                'href'          => 'act=toggle&amp;field=published',
                'icon'          => 'visible.svg',
                'showInHeader'  => true,
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['addImage', 'limitParticipants'],
        'default' => '{title_legend},title;{date_legend},startDate,startTime;{teaser_legend},teaser;{image_legend},addImage;{form_legend},form_id;{participants_legend},limitParticipants;{confirmation_legend},confirmationText;{notification_legend},notificationSubject,notificationText;{notes_legend:hide},notes;{access_legend},access_link;{publish_legend},published',
    ],

    // Sub-palettes
    'subpalettes' => [
        'addImage' => 'singleSRC',
        'limitParticipants' => 'maxParticipants,participantCount,showParticipantCount,bookedOutText',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'title' => [
            'search'    => true,
            'sorting'   => true,
            'inputType' => 'text',
            'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'startDate' => [
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DataContainer::SORT_DAY_DESC,
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'date', 'mandatory' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql'       => "int(10) unsigned NULL",
        ],
        'startTime' => [
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'time', 'tl_class' => 'w50'],
            'sql'       => "varchar(5) NOT NULL default ''",
        ],
        'teaser' => [
            'inputType' => 'textarea',
            'eval'      => ['rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql'       => "text NULL",
        ],
        'form_id' => [
            'search'           => true,
            'inputType'        => 'select',
            'options_callback' => [SponsorsEventDca::class, 'getForms'],
            'eval'             => ['mandatory' => true, 'includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql'              => "int(10) unsigned NOT NULL default 0",
        ],
        'limitParticipants' => [
            'inputType' => 'checkbox',
            'eval'      => ['submitOnChange' => true],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'maxParticipants' => [
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'natural', 'mandatory' => true, 'minval' => 1, 'maxlength' => 6, 'tl_class' => 'w50'],
            'sql'       => "int(10) unsigned NOT NULL default 0",
        ],
        // Wird vom App\EventListener\SponsorsEventParticipantListener bei jeder
        // Anmeldung hochgezählt und bleibt für die Redaktion korrigierbar (z. B. als
        // Startwert für ein Event, das schon Anmeldungen hat).
        'participantCount' => [
            'inputType' => 'text',
            'eval'      => ['rgxp' => 'natural', 'maxlength' => 6, 'doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'       => "int(10) unsigned NOT NULL default 0",
        ],
        'showParticipantCount' => [
            'inputType' => 'checkbox',
            'eval'      => ['tl_class' => 'w50 clr'],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'bookedOutText' => [
            'inputType' => 'textarea',
            'eval'      => ['rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql'       => "text NULL",
        ],
        'confirmationText' => [
            'inputType' => 'textarea',
            'eval'      => ['rte' => 'tinyMCE', 'basicEntities' => true, 'tl_class' => 'clr'],
            'sql'       => "text NULL",
        ],
        'notificationSubject' => [
            'inputType' => 'text',
            'eval'      => ['maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'long clr'],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'notificationText' => [
            'inputType' => 'textarea',
            // decodeEntities wie beim Textfeld des Notification Centers: gespeichert
            // wird genau der eingegebene Rohtext.
            'eval'      => ['rte' => false, 'decodeEntities' => true, 'tl_class' => 'clr'],
            'sql'       => "text NULL",
        ],
        'notes' => [
            'inputType' => 'textarea',
            'eval'      => ['rte' => false, 'tl_class' => 'clr'],
            'sql'       => "text NULL",
        ],
        'addImage' => [
            'inputType' => 'checkbox',
            'eval'      => ['submitOnChange' => true],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
        'singleSRC' => [
            'inputType' => 'fileTree',
            'eval'      => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'mandatory' => true],
            'sql'       => "binary(16) NULL",
        ],
        'access_token' => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'access_link' => [
            'inputType'     => 'text',
            'eval'          => ['readonly' => true, 'doNotSave' => true, 'tl_class' => 'w100 clr'],
            'load_callback' => [[SponsorsEventDca::class, 'generateAccessLink']],
        ],
        'calendar_event_id' => [
            'sql' => "int(10) unsigned NOT NULL default 0",
        ],
        'published' => [
            'toggle'    => true,
            'filter'    => true,
            'inputType' => 'checkbox',
            'eval'      => ['doNotCopy' => true, 'tl_class' => 'w50'],
            'sql'       => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
