<?php

namespace App\Tilastot\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use App\Tilastot\Model\Standings;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StandingsModule extends AbstractFrontendModuleController
{
  protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
  {
    $standings = Standings::findByRound($model->tilastot_round, array('order' => 'points DESC, goalsfor-goalsagainst DESC, goalsfor DESC, goalsagainst ASC'));
    if (!$standings) {
      return null;
    }
    $standings = $standings->fetchAll();

    if ($model->tilastot_table_rows > 0) {
      $startKey = 0;
      $r = 1;
      foreach ($standings as $key => $team) {
        $standings[$key]['rank'] = $r++;
        $standings[$key]['logo'] = Standings::parseLogo($standings[$key]['logo']);
        if ($team['tilastotid'] == $model->tilastot_my_team) {
          if ($key >= $model->tilastot_table_rows) {
            $startKey += ($key - $model->tilastot_table_rows) + 3;
          }
          if (($startKey + $model->tilastot_table_rows) > count($standings)) {
            $startKey = count($standings) - $model->tilastot_table_rows;
          }
        }
      }

      $standings = array_slice($standings, $startKey, $model->tilastot_table_rows);
    }

    $template->my_team = $model->tilastot_my_team;
    $template->standings = $standings;
    $template->columns = deserialize($model->tilastot_standings_columns);
    $template->headline = $model->headline;
    $template->headlineUnit = $model->hl;
    $template->cssId = $model->cssID[0];
    $template->cssClass = $model->cssID[1];

    return $template->getResponse();
  }
}
