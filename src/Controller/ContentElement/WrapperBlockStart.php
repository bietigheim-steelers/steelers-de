<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement('rowStart', category: 'wrapper')]
class WrapperBlockStart extends AbstractContentElementController
{
  protected function getResponse(Template $template, ContentModel $model, Request $request): Response
  {
    $template->headline = unserialize($model->headline);
    $css = unserialize($model->cssID);
    $template->cssID = $css[0];
    $template->css = $css[1];

    return $template->getResponse();
  }
}