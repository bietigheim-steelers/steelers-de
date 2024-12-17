<?php

use App\Tilastot\Model\Standings;
use Contao\NewsArchiveModel;
use Contao\System;

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

$GLOBALS['TL_DCA']['tl_news']['config']['onsubmit_callback'][] = array('tl_games_for_news', 'updateVideoportal');

class tl_games_for_news extends Backend
{
  public function getGamesForSelect()
  {
    $options = array(0 => '');

    $games = Database::getInstance()->prepare("SELECT id, hometeam, awayteam, gamedate, round FROM tl_tilastot_client_games WHERE gamedate < ? ORDER BY gamedate DESC LIMIT 5")
      ->execute(time());

    while ($games->next()) {
      $homeTeam = Standings::getTeamData($games->hometeam, $games->round);
      $awayTeam = Standings::getTeamData($games->awayteam, $games->round);
      $options[$games->id] = sprintf('%s - %s vs. %s', date('d.m.Y', $games->gamedate), $homeTeam, $awayTeam);
    }

    array_push($options, '-----');

    $games = Database::getInstance()->prepare("SELECT id, hometeam, awayteam, gamedate, round FROM tl_tilastot_client_games WHERE gamedate > ? ORDER BY gamedate ASC LIMIT 5")
      ->execute(time());

    while ($games->next()) {
      $homeTeam = Standings::getTeamData($games->hometeam, $games->round);
      $awayTeam = Standings::getTeamData($games->awayteam, $games->round);
      $options[$games->id] = sprintf('%s - %s vs. %s', date('d.m.Y', $games->gamedate), $homeTeam, $awayTeam);
    }

    return $options;
  }

  public function updateVideoportal(DataContainer $dc) {
    // Return if there is no active record (override all)
    if (!$dc->activeRecord) {
      return;
    }

    // Return if this is not the newsportal
    if ($dc->activeRecord->pid != 7 || $dc->activeRecord->game_id == 0) {
      return;
    }

    $aliasExists = function (string $alias) use ($dc): bool {
      return $this->Database->prepare("SELECT id FROM tl_news WHERE alias=? AND id!=?")->execute($alias, $dc->id)->numRows > 0;
    };

    $category = null;

    if (in_array(29, $dc->activeRecords->categories)) {
      $category = 'Highlights';
    } elseif (in_array(33, $dc->activeRecord->categories)) {
      $category = 'Impressionen';
    } elseif (in_array(30, $dc->activeRecord->categories)) {
      $category = 'Pressekonferenz';
    } elseif (in_array(35, $dc->activeRecord->categories)) {
      $category = 'Razorsharp';
    }

    $game = \App\Tilastot\Model\Games::findByPk($dc->activeRecord->game_id);
    if($game && $category) {
      $homeTeam = \App\Tilastot\Model\Standings::findByIdAndRound($game->hometeam, $game->round);
      $awayTeam = \App\Tilastot\Model\Standings::findByIdAndRound($game->awayteam, $game->round);

      $headline = Contao\Date::parse('d.m.Y', $game->gamedate) . " - " . $category . " - " . $homeTeam->name . " vs. " . $awayTeam->name;
  
      $arrSet['alias'] = System::getContainer()->get('contao.slug')->generate($headline, NewsArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
      $arrSet['headline'] = $headline;

      $this->Database->prepare("UPDATE tl_news %s WHERE id=?")->set($arrSet)->execute($dc->id);
    }


  }
}
