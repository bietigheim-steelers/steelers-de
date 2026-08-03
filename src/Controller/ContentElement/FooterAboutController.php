<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Linke Spalte der mittleren Footer-Zeile: Logo, Text und optional ein Formular
 * (z. B. Newsletter-Anmeldung).
 *
 * Das Logo ist bewusst fest im Template hinterlegt - identisch zum Header.
 */
#[AsContentElement(type: 'footer_about', category: 'business_footer')]
class FooterAboutController extends AbstractFooterElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $this->initializeContaoFramework();

        $template->set('text', $model->text ?: '');
        $template->set('form', $model->footerForm ? Controller::getForm($model->footerForm) : '');

        return $template->getResponse();
    }
}
