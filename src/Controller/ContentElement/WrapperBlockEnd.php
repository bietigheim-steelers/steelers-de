<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement(category: 'wrapper')]
class WrapperBlockEnd extends AbstractContentElementController
{
  protected function getResponse(Template $template, ContentModel $model, Request $request): Response
  {
    return $template->getResponse();
  }
}