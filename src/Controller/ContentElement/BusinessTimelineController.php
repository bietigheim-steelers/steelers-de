<?php

declare(strict_types=1);

namespace App\Controller\ContentElement;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zeitstrahl des Business-Themes ("about-us-our-journey" aus about-us.html).
 *
 * Das Theme-Markup ist auf genau vier Eintraege je Block ausgelegt: Desktop
 * vier Spalten mit waagerechter Linie, mobil zwei Spalten mit senkrechter
 * Linie. Deshalb werden die Eintraege hier in Vierergruppen zerlegt – jede
 * Gruppe bekommt ihren eigenen [data-journey-section]-Block samt Linie.
 */
#[AsContentElement(type: 'business_timeline', category: 'business_elements')]
class BusinessTimelineController extends AbstractContentElementController
{
    private const ENTRIES_PER_ROW = 4;

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $entries = $this->parseEntries($model->timelineEntries);

        $template->set('text', $model->text ?: '');
        $template->set('label', trim((string) $model->businessLabel));
        $template->set('groups', array_chunk($entries, self::ENTRIES_PER_ROW));

        return $template->getResponse();
    }

    /**
     * @return list<array{year: string, text: string}>
     */
    private function parseEntries(mixed $value): array
    {
        $entries = [];

        foreach (StringUtil::deserialize($value, true) as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $year = trim((string) ($row['year'] ?? ''));
            $text = trim((string) ($row['text'] ?? ''));

            if ('' === $year && '' === $text) {
                continue;
            }

            $entries[] = ['year' => $year, 'text' => $text];
        }

        return $entries;
    }
}
