<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Contao\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Preisliste des Business-Themes ("Pricing Plan Area" aus services.html).
 *
 * Bewusst ohne den Monatlich/Jaehrlich-Umschalter des Themes: die Pakete
 * werden komplett ueber den MultiColumnWizard gepflegt, der Preiszeitraum ist
 * ein freies Textfeld ("pro Saison", "einmalig", ...).
 */
#[AsContentElement(type: 'business_pricing', category: 'business_elements')]
class BusinessPricingController extends AbstractContentElementController
{
    private const ALLOWED_COLUMNS = [2, 3, 4];

    public function __construct(private readonly InsertTagParser $insertTagParser)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $columns = (int) $model->pricingColumns;

        $template->set('text', $model->text ?: '');
        $template->set('plans', $this->parsePlans($model->pricingPlans, $request));
        $template->set('columns', \in_array($columns, self::ALLOWED_COLUMNS, true) ? $columns : 3);
        $template->set('label', trim((string) $model->businessLabel));

        return $template->getResponse();
    }

    /**
     * @return list<array{title: string, price: string, period: string, description: string, features: list<string>, href: string, linkLabel: string, target: bool, highlight: bool}>
     */
    private function parsePlans(mixed $value, Request $request): array
    {
        $plans = [];

        foreach (StringUtil::deserialize($value, true) as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $title = trim((string) ($row['title'] ?? ''));
            $price = trim((string) ($row['price'] ?? ''));

            if ('' === $title && '' === $price) {
                continue;
            }

            $plans[] = [
                'title' => $title,
                'price' => $price,
                'period' => trim((string) ($row['period'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
                'features' => $this->parseFeatures($row['features'] ?? ''),
                'href' => $this->resolveUrl($row['linkUrl'] ?? '', $request),
                'linkLabel' => trim((string) ($row['linkLabel'] ?? '')),
                'target' => (bool) ($row['linkTarget'] ?? false),
                'highlight' => (bool) ($row['highlight'] ?? false),
            ];
        }

        return $plans;
    }

    /**
     * Leistungen werden zeilenweise gepflegt – eine Zeile ergibt einen Listenpunkt.
     *
     * @return list<string>
     */
    private function parseFeatures(mixed $value): array
    {
        $lines = preg_split('/\R/', (string) $value) ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line) => '' !== $line));
    }

    /**
     * Loest Insert-Tags (z. B. {{link_url::12}} aus dem Seiten-Picker) auf und
     * ergaenzt bei relativen Adressen den Basispfad.
     */
    private function resolveUrl(mixed $url, Request $request): string
    {
        $href = trim($this->insertTagParser->replaceInline((string) $url));

        if ('' !== $href && Validator::isRelativeUrl($href)) {
            $href = $request->getBasePath().'/'.$href;
        }

        return $href;
    }
}
