<?php

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsContentElement]
class WrapperBlockEndElementController extends AbstractContentElementController
{
  protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
  {
    return $template->getResponse();
  }
}
