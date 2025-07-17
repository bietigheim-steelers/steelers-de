<?php

namespace App\Tilastot\Module;

use Contao\Database;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AuctionModule extends AbstractFrontendModuleController
{
	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{
		$objDatabase = Database::getInstance();
		$biddings_result = $objDatabase->prepare("SELECT `player`, MAX(`gebot`) as highest_bid FROM `steelers_auktion` GROUP BY `player` ORDER BY `player` DESC;")->execute()->fetchAllAssoc();
		$biddings = [];
		foreach ($biddings_result as $key => $bidding) {
			$biddings[$bidding['player']] =  $bidding['highest_bid'];
		}

		$template->biddings = $biddings;

		$players = $objDatabase->prepare("SELECT `options` FROM `tl_form_field` WHERE `id` = 55;")->execute()->fetchAllAssoc();

		$template->players = unserialize($players[0]['options']);

		return $template->getResponse();
	}
}
