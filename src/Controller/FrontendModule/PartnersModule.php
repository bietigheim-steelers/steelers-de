<?php

namespace App\Tilastot\Module;

use App\Tilastot\Model\Partners;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Contao\StringUtil;
use Contao\ArrayUtil;
use Contao\FilesModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnersModule extends AbstractFrontendModuleController
{
	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{
		$categories = deserialize($model->tilastot_partners_category);
		$category_list = implode('"|"', $categories);
		$partners = Partners::findAll(array(
			'column'  => array(
				'published=1',
				'category REGEXP \'' . $category_list . '\''
			),
			'order' => 'name ASC'
		));
		$partnerslist = array();

		if (!$partners) {
			return new Response();
		}
		foreach ($partners->fetchAll() as $p) {

			$p['logo'] = FilesModel::findByUuid(StringUtil::deserialize($p['logo']));

			$partnerslist[] = $p;
		}


		$template->partners = $partnerslist;
		$template->categories = $categories;

		$headline = unserialize($model->headline);
		$template->headline = $headline['value'];
		$template->headlineUnit = $headline['unit'];
		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}
