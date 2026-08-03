<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kontaktspalte der mittleren Footer-Zeile (Adresse, E-Mail, Telefon, ...).
 */
#[AsContentElement(type: 'footer_contact', category: 'business_footer')]
class FooterContactController extends AbstractFooterElementController
{
    private const ICONS = ['map', 'mail', 'phone', 'clock'];

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $items = [];

        foreach (StringUtil::deserialize($model->footerContacts, true) as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));

            if ('' === $label) {
                continue;
            }

            $icon = (string) ($row['icon'] ?? '');

            $items[] = [
                'icon' => \in_array($icon, self::ICONS, true) ? $icon : 'map',
                'label' => $label,
                'href' => $this->resolveUrl($row['url'] ?? '', $request),
                'target' => (bool) ($row['target'] ?? false),
            ];
        }

        $template->set('items', $items);

        return $template->getResponse();
    }
}
