<?php

namespace App\Tilastot\Module;

use App\Tilastot\Model\Rounds;
use App\Tilastot\Model\Games;
use App\Tilastot\Model\Standings;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NextGameModule extends AbstractFrontendModuleController
{
	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{
		if ($model->tilastot_my_team > 0) {
			$games = Games::findAll(array(
				'order'   => ' gamedate ASC',
				'limit'   => ' LIMIT 1',
				'column'  => array('gamedate >= ? AND hometeam = ?'),
				'value'   => array(time(), $model->tilastot_my_team, $model->tilastot_my_team)
			));
		} else {
			$games = Games::findAll(array(
				'order'   => ' gamedate ASC',
				'limit'   => ' LIMIT 1',
				'column'  => array('gamedate >= ?'),
				'value'   => array(time())
			));
		}

		if (!$games) {
			return new Response();
		}

		$gameArray = $games->fetchAll();
		foreach ($gameArray as $key => $game) {
			$gameArray[$key]['home'] = Standings::findByIdAndRound($game['hometeam'], $game['round'], true);
			$gameArray[$key]['away'] = Standings::findByIdAndRound($game['awayteam'], $game['round'], true);
			$gameArray[$key]['season'] = Rounds::findByPkFiltered($game['round'], true);

			unset($gameArray[$key]['id']);
			unset($gameArray[$key]['tstamp']);
			unset($gameArray[$key]['round']);
			unset($gameArray[$key]['gamestatus']);
			unset($gameArray[$key]['hometeam']);
			unset($gameArray[$key]['awayteam']);
			unset($gameArray[$key]['spectators']);
			unset($gameArray[$key]['periodscore']);
		}

		$template->my_team = $model->tilastot_my_team;
		$template->game = $gameArray[0];
		$template->headline = unserialize($model->headline);
		$template->headlineUnit = $model->hl;
		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}