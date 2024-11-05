<?php

use App\Tilastot\Model\Standings;

$GLOBALS['TL_DCA']['tl_news']['fields']['game_id'] = array(
  'label'                   => &$GLOBALS['TL_LANG']['tl_news']['game_id'],
  'exclude'                 => true,
  'inputType'               => 'select',
  'options_callback'        => array('tl_games_for_news', 'getGamesForSelect'),
  'eval'                    => array('mandatory' => false, 'tl_class' => 'w50'),
  'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_news']['palettes']['default'] = str_replace(
  '{date_legend}',
  '{game_legend},game_id;{date_legend}',
  $GLOBALS['TL_DCA']['tl_news']['palettes']['default']
);

class tl_games_for_news extends Backend
{
  public function getGamesForSelect()
  {
    $games = Database::getInstance()->prepare("SELECT id, hometeam, awayteam, gamedate, round FROM tl_tilastot_client_games ORDER BY gamedate DESC LIMIT 5")
      ->execute();

    $options = array();
    while ($games->next()) {
      $homeTeam = Standings::getTeamData($games->hometeam, $games->round);
      $awayTeam = Standings::getTeamData($games->awayteam, $games->round);
      $options[$games->id] = sprintf('%s - %s vs. %s', date('d.m.Y', $games->gamedate), $homeTeam, $awayTeam);
    }

    $games = Database::getInstance()->prepare("SELECT id, hometeam, awayteam, gamedate, round FROM tl_tilastot_client_games WHERE gamedate > ? ORDER BY gamedate ASC LIMIT 5")
      ->execute(time());

    while ($games->next()) {
      $homeTeam = Standings::getTeamData($games->hometeam, $games->round);
      $awayTeam = Standings::getTeamData($games->awayteam, $games->round);
      $options[$games->id] = sprintf('%s - %s vs. %s', date('d.m.Y', $games->gamedate), $homeTeam, $awayTeam);
    }

    return $options;
  }
}
