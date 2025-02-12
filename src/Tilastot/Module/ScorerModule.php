<?php

namespace App\Tilastot\Module;

use Doctrine\ORM\EntityManagerInterface;
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
	private $entityManager;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{
		$qb = $this->entityManager->createQueryBuilder();

		$qb->select('p, ps')
		->from('App\Tilastot\Model\Players', 'p')
		->leftJoin('App\Tilastot\Model\PlayerStats', 'ps', 'WITH', 'p.id = ps.pid')
		->where('p.published = 1')
		->orderBy('p.jersey', 'ASC');

		$players = $qb->getQuery()->getResult();
		$playerlist = array();

		if (!$players) {
			return new Response();
		}

		foreach ($players as $player) {
			$p = $player[0]; // Player entity
			$stats = $player['ps']; // PlayerStats entity

			$pictures = StringUtil::deserialize($p->getPictures());

			if (!empty($pictures) || is_array($pictures)) {
				$files = ArrayUtil::sortByOrderField($pictures, StringUtil::deserialize($p->getOrderPictures()));
				$allPictures = FilesModel::findMultipleByUuids($files)->fetchAll();
				$p->setProfilePic(array_shift($allPictures));
				$p->setPictures($allPictures);
			}

			$playerlist[] = [
				'player' => $p,
				'stats' => $stats
			];
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
