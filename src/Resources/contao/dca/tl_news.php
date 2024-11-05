<?php

$GLOBALS['TL_DCA']['tl_news']['fields']['game_id'] = array(
  'label'                   => &$GLOBALS['TL_LANG']['tl_news']['game_id'],
  'exclude'                 => true,
  'inputType'               => 'select',
  'options_callback'        => array('tl_games_for_news', 'getGamesForSelect'),
  'eval'                    => array('mandatory' => false, 'tl_class' => 'w50'),
  'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace(
  '{title_legend}',
  '{game_legend},game_id;{title_legend}',
  $GLOBALS['TL_DCA']['tl_news']['palettes']['default']
);

class tl_games_for_news extends Backend
{
  public function getGamesForSelect()
  {
    $time = time();
    $startDate = strtotime('-5 days', $time);
    $endDate = strtotime('+5 days', $time);

    $games = Database::getInstance()->prepare("SELECT id, hometeam, awayteam, gamedate FROM tl_tilastot_client_games WHERE gamedate BETWEEN ? AND ?")
      ->execute($startDate, $endDate);

    $options = array();
    while ($games->next()) {
      $options[$games->id] = sprintf('%s - %s vs. %s', date('d.m.Y', $games->gamedate), $games->hometeam, $games->awayteam);
    }

    return $options;
  }
}
