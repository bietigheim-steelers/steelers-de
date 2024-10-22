<?php

$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_round'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_round'],
	'exclude'                 => true,
	'search'                  => true,
	'inputType'               => 'select',
	'options_callback'        => array('App\\Tilastot\\Model\\Rounds', 'findForSelect'),
	'eval'										 => array('onchange' => 'Backend.autoSubmit(\'tl_module\')'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_table_rows'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_table_rows'],
	'exclude'                 => true,
	'inputType'               => 'text',
	'eval'                    => array('mandatory' => false, 'rgxp' => 'numeric', 'tl_class' => 'w50'),
	'sql'                     => "int(3) NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_my_team'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_my_team'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('App\\Tilastot\\Model\\Standings', 'findTeamsForSelect'),
	'eval'                    => array('tl_class' => 'w50'),
	'sql'                     => "int(5) NULL"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_standings_columns'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_standings_columns'],
	'inputType'               => 'checkboxWizard',
	'options_callback'        => array('App\\Tilastot\\Model\\Standings', 'findColumnsForSelect'),
	'reference'               => &$GLOBALS['TL_LANG']['tilastot_standings_columns'],
	'eval'                    => array('multiple' => true, 'tl_class' => 'clr'),
	'sql'                     => "blob NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_config_json'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_config_json'],
	'inputType'               => 'textarea',
	'reference'               => &$GLOBALS['TL_LANG']['tilastot_config_json'],
	'eval'                    => array('style' => 'height:60px', 'preserveTags' => true, 'rte' => 'ace|html', 'tl_class' => 'clr'),
	'sql'                     => "text NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_from_date'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_from_date'],
	'inputType'               => 'text',
	'eval'                    => array('rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
	'sql'                     => "varchar(10) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_schedule_type'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_schedule_type'],
	'inputType'               => 'select',
	'options'				  => array('results_fixtures', 'results', 'fixtures'),
	'eval'                    => array('tl_class' => 'w50 wizard'),
	'sql'                     => "varchar(20) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_to_date'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_to_date'],
	'inputType'               => 'text',
	'eval'                    => array('rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'),
	'sql'                     => "varchar(10) NOT NULL default ''"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_config_json'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_config_json'],
	'inputType'               => 'textarea',
	'reference'               => &$GLOBALS['TL_LANG']['tilastot_config_json'],
	'eval'                    => array('style' => 'height:60px', 'preserveTags' => true, 'rte' => 'ace|html', 'tl_class' => 'clr'),
	'sql'                     => "text NULL"
);
$GLOBALS['TL_DCA']['tl_module']['fields']['tilastot_partners_category'] = array(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['tilastot_partners_category'],
	'inputType'               => 'checkbox',
	'options'               => array(
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
	'eval'                    => array('multiple' => true),
	'sql'                     => "text NULL"
);

$GLOBALS['TL_DCA']['tl_module']['palettes']['schedule'] = '{title_legend},name,headline,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['schedule'] .= '{tilastot_legend},tilastot_my_team,tilastot_from_date,tilastot_to_date,tilastot_schedule_type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['schedule'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['roster'] = '{title_legend},name,headline,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['roster'] .= '{holema_legend},tilastot_round,tilastot_my_team,tilastot_config_json;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['roster'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['player'] = '{title_legend},name,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['player'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['nextgame'] = '{title_legend},name,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['nextgame'] .= '{tilastot_legend},tilastot_my_team;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['nextgame'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['standings'] = '{title_legend},name,headline,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['standings'] .= '{tilastot_legend},tilastot_round,tilastot_table_rows,tilastot_my_team,tilastot_standings_columns;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['standings'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['partners'] = '{title_legend},name,headline,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['partners'] .= '{tilastot_legend},tilastot_partners_category;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['partners'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';

$GLOBALS['TL_DCA']['tl_module']['palettes']['camps'] = '{title_legend},name,headline,type;';
$GLOBALS['TL_DCA']['tl_module']['palettes']['camps'] .= '{template_legend:hide},customTpl;{expert_legend:hide},cssID,space';
