<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;

/**
 * Gemeinsame Basis der Footer-Inhaltselemente des Business-Themes.
 *
 * Die Elemente pflegen ihre Listen ueber den MultiColumnWizard. Diese Klasse
 * normalisiert die serialisierten Zeilen zu einfachen Arrays fuer die Templates.
 */
abstract class AbstractFooterElementController extends AbstractContentElementController
{
    public function __construct(protected readonly InsertTagParser $insertTagParser)
    {
    }

    /**
     * @return list<array{href: string, label: string, title: string, target: bool, style: string}>
     */
    protected function parseLinks(mixed $value, Request $request): array
    {
        $links = [];

        foreach (StringUtil::deserialize($value, true) as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $href = $this->resolveUrl($row['url'] ?? '', $request);
            $label = trim((string) ($row['label'] ?? ''));

            if ('' === $href && '' === $label) {
                continue;
            }

            $links[] = [
                'href' => $href,
                'label' => $label ?: $href,
                'title' => trim((string) ($row['titleText'] ?? '')),
                'target' => (bool) ($row['target'] ?? false),
                'style' => 'outline' === ($row['style'] ?? '') ? 'outline' : 'primary',
            ];
        }

        return $links;
    }

    /**
     * Loest Insert-Tags (z. B. {{link_url::12}} aus dem Seiten-Picker) auf und
     * ergaenzt bei relativen Adressen den Basispfad.
     */
    protected function resolveUrl(mixed $url, Request $request): string
    {
        $href = trim($this->insertTagParser->replaceInline((string) $url));

        if ('' !== $href && Validator::isRelativeUrl($href)) {
            $href = $request->getBasePath().'/'.$href;
        }

        return $href;
    }
}
