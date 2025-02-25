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
		$biddings = $objDatabase->prepare("SELECT `player`, MAX(`gebot`) FROM `steelers_auktion` GROUP BY `player` ORDER BY `player` DESC;")->execute()->fetchAllAssoc();

		$template->biddings = $biddings;

		$players = $objDatabase->prepare("SELECT `options` FROM `tl_form_field` WHERE `id` = 55;")->execute()->fetchAllAssoc();

		$template->players = $players;

		return $template->getResponse();
	}
}
