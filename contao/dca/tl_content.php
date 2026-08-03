<?php

// wrapper
$GLOBALS['TL_DCA']['tl_content']['palettes']['wrapper_block_start_element'] = '{type_legend},type;{link_legend},headline;{expert_legend:hide},cssID;{template_legend:hide},customTpl;';
$GLOBALS['TL_DCA']['tl_content']['palettes']['wrapper_block_end_element'] = '{type_legend},type;{template_legend:hide},customTpl;';
$GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = '{type_legend},type,headline;{text_legend},text;{link_legend},url,target,linkTitle,embed,titleText,rel;{image_legend},addImage;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['template'] = '{type_legend},type,headline;{template_legend},data,customTpl;{image_legend},addImage;{protected_legend:hide},protected;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['fields']['url']['eval']['mandatory'] = false;

/*
 * Footer-Elemente des Business-Themes (business.steelers.de).
 *
 * Die Elemente werden auf einer versteckten Ressourcen-Seite in einem Artikel
 * gepflegt und ueber ein "Artikel"-Modul im Footer-Bereich des Layouts ausgegeben.
 */

$footerLinkColumns = array(
	'label' => array(
		'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerLink_label'],
		'inputType' => 'text',
		'eval'      => array('mandatory' => true, 'style' => 'width:220px'),
	),
	'url' => array(
		'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerLink_url'],
		'inputType' => 'text',
		'eval'      => array('rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'dcaPicker' => true, 'style' => 'width:320px'),
	),
	'target' => array(
		'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerLink_target'],
		'inputType' => 'checkbox',
		'eval'      => array('style' => 'width:30px'),
	),
);

$GLOBALS['TL_DCA']['tl_content']['fields']['footerButtons'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerButtons'],
	'exclude'   => true,
	'inputType' => 'multiColumnWizard',
	'eval'      => array(
		'tl_class'     => 'clr',
		'columnFields' => array_merge(
			$footerLinkColumns,
			array(
				'style' => array(
					'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerLink_style'],
					'inputType' => 'select',
					'options'   => array('primary', 'outline'),
					'reference' => &$GLOBALS['TL_LANG']['tl_content']['footerLink_styles'],
					'eval'      => array('style' => 'width:140px'),
				),
			)
		),
	),
	'sql'       => "blob NULL",
);

$GLOBALS['TL_DCA']['tl_content']['fields']['footerLinks'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerLinks'],
	'exclude'   => true,
	'inputType' => 'multiColumnWizard',
	'eval'      => array(
		'tl_class'     => 'clr',
		'columnFields' => $footerLinkColumns,
	),
	'sql'       => "blob NULL",
);

$GLOBALS['TL_DCA']['tl_content']['fields']['footerContacts'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerContacts'],
	'exclude'   => true,
	'inputType' => 'multiColumnWizard',
	'eval'      => array(
		'tl_class'     => 'clr',
		'columnFields' => array(
			'icon' => array(
				'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerContact_icon'],
				'inputType' => 'select',
				'options'   => array('map', 'mail', 'phone', 'clock'),
				'reference' => &$GLOBALS['TL_LANG']['tl_content']['footerContact_icons'],
				'eval'      => array('style' => 'width:140px'),
			),
			'label' => array(
				'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerContact_label'],
				'inputType' => 'text',
				'eval'      => array('mandatory' => true, 'style' => 'width:300px'),
			),
			'url' => array(
				'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerContact_url'],
				'inputType' => 'text',
				'eval'      => array('decodeEntities' => true, 'maxlength' => 2048, 'dcaPicker' => true, 'style' => 'width:300px'),
			),
			'target' => array(
				'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerLink_target'],
				'inputType' => 'checkbox',
				'eval'      => array('style' => 'width:30px'),
			),
		),
	),
	'sql'       => "blob NULL",
);

$GLOBALS['TL_DCA']['tl_content']['fields']['footerSocials'] = array(
	'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerSocials'],
	'exclude'   => true,
	'inputType' => 'multiColumnWizard',
	'eval'      => array(
		'tl_class'     => 'clr',
		'columnFields' => array(
			'network' => array(
				'label'            => &$GLOBALS['TL_LANG']['tl_content']['footerSocial_network'],
				'inputType'        => 'select',
				'options_callback' => array('App\\Controller\\ContentElement\\FooterBottomController', 'getNetworkOptions'),
				'eval'             => array('style' => 'width:160px'),
			),
			'url' => array(
				'label'     => &$GLOBALS['TL_LANG']['tl_content']['footerSocial_url'],
				'inputType' => 'text',
				'eval'      => array('mandatory' => true, 'rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'style' => 'width:420px'),
			),
		),
	),
	'sql'       => "blob NULL",
);

// Eigenes Feld, weil das Kernfeld "form" zwingend erforderlich ist - im Footer ist das Formular optional.
$GLOBALS['TL_DCA']['tl_content']['fields']['footerForm'] = array(
	'label'            => &$GLOBALS['TL_LANG']['tl_content']['footerForm'],
	'exclude'          => true,
	'inputType'        => 'select',
	'foreignKey'       => 'tl_form.title',
	'options_callback' => array('tl_content', 'getForms'),
	'eval'             => array('includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'),
	'sql'              => "int(10) unsigned NOT NULL default 0",
	'relation'         => array('type' => 'hasOne', 'load' => 'lazy'),
);

unset($footerLinkColumns);

$GLOBALS['TL_DCA']['tl_content']['palettes']['footer_cta'] = '{type_legend},type,headline;{text_legend},text;{link_legend},footerButtons;{template_legend:hide},customTpl;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['footer_about'] = '{type_legend},type;{text_legend},text;{form_legend},footerForm;{template_legend:hide},customTpl;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['footer_linklist'] = '{type_legend},type,headline;{link_legend},footerLinks;{template_legend:hide},customTpl;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['footer_contact'] = '{type_legend},type,headline;{link_legend},footerContacts;{template_legend:hide},customTpl;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';
$GLOBALS['TL_DCA']['tl_content']['palettes']['footer_bottom'] = '{type_legend},type;{text_legend},text;{link_legend},footerLinks;{footer_social_legend},footerSocials;{template_legend:hide},customTpl;{expert_legend:hide},cssID;{invisible_legend:hide},invisible,start,stop';