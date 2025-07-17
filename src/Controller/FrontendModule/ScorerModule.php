<?php

namespace App\Module;

use Contao\Database;

use Contao\CoreBundle\Image\ImageFactoryInterface;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Contao\StringUtil;
use Contao\ArrayUtil;
use Contao\FilesModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ScorerModule extends AbstractFrontendModuleController
{

	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{

		$objDatabase = Database::getInstance();
		$players = $objDatabase->prepare("
    SELECT * 
    FROM tl_tilastot_client_players 
    LEFT JOIN tl_tilastot_client_stats 
    ON (tl_tilastot_client_players.id = tl_tilastot_client_stats.pid) 
    WHERE tl_tilastot_client_players.published = ? 
    AND tl_tilastot_client_stats.games > 0 
    ORDER BY tl_tilastot_client_stats.points DESC
")->execute('1')->fetchAllAssoc();
		$playerlist = array();

		if (!$players) {
			return new Response();
		}

		foreach ($players as $p) {
			$pictures = StringUtil::deserialize($p['pictures']);

			if (!empty($pictures) || is_array($pictures)) {
				$files = ArrayUtil::sortByOrderField($pictures, StringUtil::deserialize($p['orderPictures']));
				$allPictures = FilesModel::findMultipleByUuids($files)->fetchAll();
				$p['profilePic'] = array_shift($allPictures);
				$p['pictures'] = $allPictures;
			}

			$playerlist[] = $p;
		}

		$template->players = $playerlist;
		$template->configData = json_decode(preg_replace('/(\v|\s)+/', ' ', $model->tilastot_config_json), true);

		$template->headline = $model->headline;
		$template->headlineUnit = $model->hl;
		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}
