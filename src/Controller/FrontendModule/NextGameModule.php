<?php

namespace App\Controller\FrontendModule;

use App\Model\Rounds;
use App\Model\Games;
use App\Model\Standings;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'steelers_season_modules')]
class NextGameModule extends AbstractFrontendModuleController
{
	protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
	{
		if ($model->tilastot_my_team > 0) {
			$games = Games::findAll(array(
				'order'   => ' gamedate ASC',
				'limit'   => ' LIMIT 1',
				'column'  => array('gamedate >= ? AND hometeam = ?'),
				'value'   => array(time(), $model->tilastot_my_team)
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

		$game = $gameArray[0];
		
		// Calculate game timing
		$today = date('Ymd');
		$tomorrow = date('Ymd', time() + 24 * 60 * 60);
		$gameday = date('Ymd', $game['gamedate']);
		
		$title = 'Nächstes Heimspiel';
		$addClasses = '';
		$iconClasses = '';
		
		if ($today == $gameday) {
			$title = 'Heute Heimspiel!';
			$iconClasses = 'animate-wiggle';
			$addClasses = 'game_is_today';
		} elseif ($tomorrow == $gameday) {
			$title = 'Morgen Heimspiel!';
			$addClasses = 'game_is_tomorrow';
		}
		
		// Determine ticket link
		$ticketUrl = '/online-tickets';
		if (!empty($game['eventimurl']) && filter_var($game['eventimurl'], FILTER_VALIDATE_URL)) {
			$ticketUrl = $game['eventimurl'];
		}

		$template->my_team = $model->tilastot_my_team;
		$template->game = $game;
		$template->title = $title;
		$template->addClasses = $addClasses;
		$template->iconClasses = $iconClasses;
		$template->ticketUrl = $ticketUrl;
		$template->headline = unserialize($model->headline);
		$template->headlineUnit = $model->hl;
		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}
