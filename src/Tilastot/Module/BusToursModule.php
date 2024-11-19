
<?php

namespace App\Tilastot\Module;

use App\Tilastot\Model\BusTours;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BusToursModule extends AbstractFrontendModuleController
{
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $bustours = BusTours::findAll(array(
            'column'  => array(
                'published=1',
            ),
            'order' => 'tourdate ASC'
        ));

        $bustourslist = array();

        if (!$bustours) {
            return new Response();
        }

        $template->bustours = $bustours->fetchAll();

        $template->cssId = $model->cssID[0];
        $template->cssClass = $model->cssID[1];

        return $template->getResponse();
    }
}