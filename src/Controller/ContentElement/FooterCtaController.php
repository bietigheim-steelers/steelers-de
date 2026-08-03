<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Obere Footer-Zeile: Ueberschrift, Text und Buttons.
 */
#[AsContentElement(type: 'footer_cta', category: 'business_footer')]
class FooterCtaController extends AbstractFooterElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('text', $model->text ?: '');
        $template->set('links', $this->parseLinks($model->footerButtons, $request));

        return $template->getResponse();
    }
}
