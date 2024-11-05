<?php

$GLOBALS['TL_DCA']['tl_news']['fields']['game_id'] = array(
  'label'                   => &$GLOBALS['TL_LANG']['tl_news']['game_id'],
  'exclude'                 => true,
  'inputType'               => 'select',
  'foreignKey'              => 'tl_tilastot_client_games.id',
  'eval'                    => array('mandatory' => false, 'tl_class' => 'w50'),
  'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace(
  '{title_legend}',
  '{game_legend},game_id;{title_legend}',
  $GLOBALS['TL_DCA']['tl_news']['palettes']['default']
);
