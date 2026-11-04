<?php

namespace App\Api;

use App\Model\AppBlock;
use Contao\StringUtil;

/**
 * Wandelt einen im Backend gepflegten Baustein (tl_app_block) in die API-Struktur
 * (docs/openapi.yaml: Block). Liefert eine Liste, weil eingebundene Artikel mehrere Bausteine ergeben
 * und unvollständige Bausteine (z. B. Button ohne Link) ganz entfallen.
 */
class AppBlockBuilder
{
    public function __construct(
        private readonly ContentFormatter $format,
        private readonly ImageUrlBuilder $images,
        private readonly ContentBlockMapper $content,
    ) {
    }

    /**
     * @return list<array>
     */
    public function build(AppBlock $b): array
    {
        $title = $this->format->text($b->blockTitle);

        $block = match ($b->blockType) {
            'headline' => $title === null ? null : [
                'type' => 'headline',
                'text' => $title,
                'level' => max(1, min(4, (int) substr((string) $b->blockLevel, 1) ?: 2)),
            ],
            'text' => ($html = $this->format->html($b->blockText)) === null ? null : ['type' => 'text', 'html' => $html],
            'image' => $this->image($b),
            'list' => $this->list($b),
            'button' => $this->button($b),
            'download' => $this->content->download($b->blockFile, $b->blockLabel),
            'video' => $this->content->video((string) $b->blockProvider, (string) $b->blockVideoId, $title),
            'promo' => $this->promo($b, $title),
            'news_list' => [
                'type' => 'news_list',
                'title' => $title,
                'limit' => (int) $b->blockLimit > 0 ? (int) $b->blockLimit : 5,
                'category' => $this->format->text($b->blockCategory),
                'layout' => $b->blockLayout === 'carousel' ? 'carousel' : 'list',
            ],
            'next_game' => ['type' => 'next_game', 'title' => $title],
            'games' => [
                'type' => 'games',
                'title' => $title,
                'status' => $b->blockStatus === 'finished' ? 'finished' : 'upcoming',
                'limit' => (int) $b->blockLimit > 0 ? (int) $b->blockLimit : null,
                'layout' => $b->blockStatus === 'slider' ? 'slider' : 'list',
            ],
            'standings' => ['type' => 'standings', 'title' => $title, 'compact' => (bool) $b->blockCompact],
            'roster' => ['type' => 'roster', 'title' => $title, 'position' => $this->format->text($b->blockPosition)],
            'partners' => [
                'type' => 'partners',
                'title' => $title,
                'category' => $this->format->text($b->blockPartnerCategory),
                'layout' => $b->blockLayout === 'slider' ? 'slider' : 'grid',
            ],
            default => null,
        };

        return match ($b->blockType) {
            'article' => $this->content->forArticle((int) $b->blockArticle),
            'content_element' => ($element = $this->content->forElementId((int) $b->blockContent)) === null ? [] : [$element],
            default => $block === null ? [] : [$block],
        };
    }

    private function image(AppBlock $b): ?array
    {
        $image = $this->images->fromUuid($b->blockImage, 1000, null, $this->format->text($b->blockAlt), $this->format->text($b->blockCaption));

        return $image === null ? null : ['type' => 'image', 'image' => $image, 'linkUrl' => $this->format->url($b->blockUrl)];
    }

    private function list(AppBlock $b): ?array
    {
        $items = [];
        foreach (preg_split('/\R/u', (string) $b->blockItems) ?: [] as $line) {
            if (($text = $this->format->text($line)) !== null) {
                $items[] = $text;
            }
        }

        return $items === [] ? null : ['type' => 'list', 'ordered' => (bool) $b->blockOrdered, 'items' => $items];
    }

    private function button(AppBlock $b): ?array
    {
        $url = $this->format->url($b->blockUrl);
        $label = $this->format->text($b->blockLabel);

        return $url === null || $label === null ? null : [
            'type' => 'button',
            'label' => $label,
            'url' => $url,
            'external' => !str_starts_with($url, 'app://'),
        ];
    }

    private function promo(AppBlock $b, ?string $title): ?array
    {
        if ($title === null) {
            return null;
        }

        return [
            'type' => 'promo',
            'title' => $title,
            'text' => $this->format->text($b->blockPlain),
            'image' => $this->images->fromUuid($b->blockImage, 1000, null, $title),
            'label' => $this->format->text($b->blockLabel),
            'url' => $this->format->url($b->blockUrl),
        ];
    }
}
