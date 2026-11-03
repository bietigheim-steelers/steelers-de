<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kids-Club-Teaser fuer die Startseite: gruenes Band, Ella ragt oben und unten heraus.
 *
 * Alle Texte, das Bild und das Linkziel kommen aus dem Inhaltselement.
 */
#[AsContentElement(type: 'kidsclub_teaser', category: 'texts')]
class KidsClubTeaserController extends AbstractContentElementController
{
    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $href = trim($this->insertTagParser->replaceInline((string) $model->url));

        if ('' !== $href && Validator::isRelativeUrl($href)) {
            $href = $request->getBasePath().'/'.$href;
        }

        $template->set('text', $model->text ?: '');
        $template->set('badge', trim((string) $model->kidsBadge));
        $template->set('href', $href);
        $template->set('link_label', trim((string) $model->linkTitle));

        return $template->getResponse();
    }
}
