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
class SchedulePlayoffsModule extends AbstractFrontendModuleController
{
	protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
	{
		$column = array('gamedate >= ? AND gamedate <= ?');
		$array = array($model->tilastot_from_date, $model->tilastot_to_date);
		$order = ' gamedate ASC';
		
		if ($model->tilastot_my_team > 0) {
			$column[0] .= ' AND (awayteam = ? OR hometeam = ?)';
			array_push($array, $model->tilastot_my_team);
			array_push($array, $model->tilastot_my_team);
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
		}

		// Process playoff series
		$po_series = [];
		$series_id = '';

		foreach ($gameArray as $key => $game) {
			$series_id = $game['home']['shortname'] == 'SCB' 
				? $game['home']['shortname'] . '-' . $game['away']['shortname'] 
				: $game['away']['shortname'] . '-' . $game['home']['shortname'];
			
			if (isset($po_series[$series_id]['games'])) {
				// this is not the first game of the series
				$po_series[$series_id]['games'][] = $game;
			} else {
				// this is the first game of the series, so setup the series
				$po_series[$series_id]['home'] = $game['home'];
				$po_series[$series_id]['away'] = $game['away'];
				$po_series[$series_id]['games'] = [$game];
				$po_series[$series_id]['wins_home'] = 0;
				$po_series[$series_id]['wins_away'] = 0;
				
				$gamedayParts = explode('-', $game['gameday']);
				if ($gamedayParts[0] == '1/8') {
					$po_series[$series_id]['round'] = "Achtelfinale";
				} else if ($gamedayParts[0] == '1/4') {
					$po_series[$series_id]['round'] = "Viertelfinale";
				} else if ($gamedayParts[0] == '1/2') {
					$po_series[$series_id]['round'] = "Halbfinale";
				} else if ($gamedayParts[0] == '1/1') {
					$po_series[$series_id]['round'] = "Finale";
				}
			}

			if ($game['ended']) {
				if ($po_series[$series_id]['home'] == $game['home']) {
					if ($game['homescore'] > $game['awayscore']) {
						$po_series[$series_id]['wins_home']++;
					} else {
						$po_series[$series_id]['wins_away']++;
					}
				} else {
					if ($game['homescore'] > $game['awayscore']) {
						$po_series[$series_id]['wins_away']++;
					} else {
						$po_series[$series_id]['wins_home']++;
					}
				}
			}
		}

		if (!empty($series_id) && isset($po_series[$series_id])) {
			$current_series = $po_series[$series_id];
			
			$current_series['bestof'] = count($current_series['games']);
			$current_series['wins_needed'] = ceil($current_series['bestof'] / 2);
			$current_series['games_needed'] = $current_series['wins_needed'] 
				- max([$current_series['wins_away'], $current_series['wins_home']]) 
				+ $current_series['wins_away'] 
				+ $current_series['wins_home'];

			// Filter games that should be displayed
			$displayGames = [];
			foreach ($current_series['games'] as $k => $game) {
				if (
					max([$current_series['wins_away'], $current_series['wins_home']]) == $current_series['wins_needed']
					&& $k >= $current_series['games_needed']
				) {
					break;
				}
				$displayGames[] = $game;
			}
			$current_series['display_games'] = $displayGames;

			$template->current_series = $current_series;
			$template->has_data = true;
		} else {
			$template->has_data = false;
		}

		$template->headline = unserialize($model->headline);
		$template->headlineUnit = $model->hl;
		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}
