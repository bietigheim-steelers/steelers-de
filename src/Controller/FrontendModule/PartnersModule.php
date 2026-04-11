<?php

namespace App\Controller\FrontendModule;

use App\Model\Partners;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Contao\FilesModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'miscellaneous')]
class PartnersModule extends AbstractFrontendModuleController
{
	protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
	{
		$categories = unserialize($model->tilastot_partners_category);
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
