<?php

namespace App\Tilastot\Module;

use Contao\Module;
use App\Tilastot\Model\Rounds;
use App\Tilastot\Model\Games;
use App\Tilastot\Model\Standings;


use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleModule extends AbstractFrontendModuleController
{
	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{
		$column = array('gamedate >= ? AND gamedate <= ?');
		$array = array($model->tilastot_from_date, $model->tilastot_to_date);
		$order = ' gamedate ASC';
		// tilastot_schedule_type
		if ($model->tilastot_my_team > 0) {
			$column[0] .= ' AND (awayteam = ? OR hometeam = ?)';
			array_push($array, $model->tilastot_my_team);
			array_push($array, $model->tilastot_my_team);
		}
		if($model->tilastot_schedule_type === 'results') {
			$column[0] .= ' AND gamedate <= ?';
			$order = ' gamedate DESC';
			array_push($array, time());
		}
		if($model->tilastot_schedule_type === 'fixtures') {
			$column[0] .= ' AND gamedate >= ?';
			array_push($array, time());
		}
		$games = Games::findAll(array(
			'order'   => $order,
			'column'  => $column,
			'value'   => $array
		));

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
		$template->games = $gameArray;
		$template->headline = unserialize($model->headline);
		$template->headlineUnit = $model->hl;
		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}