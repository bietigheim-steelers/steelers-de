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
 * Untere Footer-Zeile: Copyright, Rechtslinks und Social-Media-Icons.
 */
#[AsContentElement(type: 'footer_bottom', category: 'business_footer')]
class FooterBottomController extends AbstractFooterElementController
{
    private const NETWORKS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'twitter' => 'X',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
        'linkedin' => 'LinkedIn',
        'whatsapp' => 'WhatsApp',
        'vimeo' => 'Vimeo',
        'pinterest' => 'Pinterest',
    ];

    /**
     * @return array<string, string>
     */
    public static function getNetworkOptions(): array
    {
        return self::NETWORKS;
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $socials = [];

        foreach (StringUtil::deserialize($model->footerSocials, true) as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $network = (string) ($row['network'] ?? '');
            $href = $this->resolveUrl($row['url'] ?? '', $request);

            if ('' === $href || !isset(self::NETWORKS[$network])) {
                continue;
            }

            $socials[] = [
                'network' => $network,
                'href' => $href,
                'label' => self::NETWORKS[$network],
            ];
        }

        $template->set('text', $model->text ?: '');
        $template->set('links', $this->parseLinks($model->footerLinks, $request));
        $template->set('socials', $socials);

        return $template->getResponse();
    }
}
