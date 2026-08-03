<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Eine Linkspalte der mittleren Footer-Zeile.
 */
#[AsContentElement(type: 'footer_linklist', category: 'business_footer')]
class FooterLinkListController extends AbstractFooterElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('links', $this->parseLinks($model->footerLinks, $request));

        return $template->getResponse();
    }
}
