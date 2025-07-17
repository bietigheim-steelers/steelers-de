<?php

namespace App\Module;

use App\Model\Camps;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Contao\StringUtil;
use Contao\ArrayUtil;
use Contao\FilesModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CampsModule extends AbstractFrontendModuleController
{
	protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
	{
		$camps = Camps::findAll(array(
			'order' => 'startdate ASC',
			'column'  => array('published=?', 'startdate>?'),
			'value'   => array(1, time())
		));

		$campslist = array();

		if (!$camps) {
			return new Response();
		}
		foreach ($camps->fetchAll() as $p) {
			$campslist[$p['category']][] = $p;
		}


		$template->camps = $campslist;

		$template->cssId = $model->cssID[0];
		$template->cssClass = $model->cssID[1];

		return $template->getResponse();
	}
}
