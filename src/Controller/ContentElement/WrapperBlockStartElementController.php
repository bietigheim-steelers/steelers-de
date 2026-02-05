<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement]
class WrapperBlockStartElementController extends AbstractContentElementController
{
  protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
  {
    $template->headline = unserialize($model->headline);
    $css = unserialize($model->cssID);
    $template->cssID = $css[0];
    $template->css = $css[1];

    return $template->getResponse();
  }
}
