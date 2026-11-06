<?php

namespace App\Api;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Date;
use Contao\File;
use Contao\FilesModel;
use Contao\FormModel;
use Contao\StringUtil;

/**
 * Übersetzt Inhaltselemente der Webseite (tl_content) in App-Bausteine (docs/openapi.yaml: Block).
 * Unterstützt: headline (auch als Überschrift anderer Elemente), text, image, list, hyperlink,
 * download, youtube, vimeo, Formulare (als Button zur Webseite) und verschachtelte Gruppen.
 * Alles andere (Module, Slider-Inhalte …) hat keine App-Darstellung und entfällt.
 */
class ContentBlockMapper
{
    /** Elemente, deren Kinder (ptable tl_content) mit ausgegeben werden. */
    private const CONTAINERS = ['element_group', 'accordion', 'content_slider', 'accordionSingle', 'sliderStart'];

    public function __construct(
        private readonly ContentFormatter $format,
        private readonly ImageUrlBuilder $images,
    ) {
    }

    /**
     * Alle veröffentlichten Elemente eines veröffentlichten Artikels.
     *
     * @return list<array>
     */
    public function forArticle(int $articleId): array
    {
        if ($articleId <= 0 || ArticleModel::findPublishedById($articleId) === null) {
            return [];
        }

        $blocks = [];
        foreach (ContentModel::findPublishedByPidAndTable($articleId, 'tl_article') ?? [] as $element) {
            array_push($blocks, ...$this->forElement($element));
        }

        return $blocks;
    }

    /**
     * Ein einzelnes Element; nur wenn es selbst und sein Artikel veröffentlicht sind.
     */
    /**
     * @return list<array>
     */
    public function forElementId(int $id): array
    {
        $time = Date::floorToMinute();
        $element = ContentModel::findOneBy(
            ['id=?', "invisible=0 AND (start='' OR start<=$time) AND (stop='' OR stop>$time)"],
            [$id],
        );

        if ($element === null || $element->ptable !== 'tl_article' || ArticleModel::findPublishedById((int) $element->pid) === null) {
            return [];
        }

        return $this->forElement($element);
    }

    /**
     * Ein Element samt eigener Überschrift (Feld "headline" gibt es an fast jedem Element) und,
     * bei Gruppen/Akkordeons/Slidern, seiner verschachtelten Kindelemente.
     *
     * @return list<array>
     */
    public function forElement(ContentModel $element): array
    {
        $blocks = [];

        if ($element->type !== 'headline' && ($heading = $this->headline($element)) !== null) {
            $blocks[] = $heading;
        }

        $block = match ($element->type) {
            'headline' => $this->headline($element),
            'text' => $this->text($element),
            'image' => $this->image($element),
            'list' => $this->list($element),
            'hyperlink' => $this->button($element),
            'download' => $this->download($element->singleSRC, $element->linkTitle),
            'youtube' => $this->video('youtube', (string) $element->youtube, null),
            'vimeo' => $this->video('vimeo', (string) $element->vimeo, null),
            'form' => $this->form($element),
            default => null,
        };

        if ($block !== null) {
            $blocks[] = $block;
        }

        if (\in_array($element->type, self::CONTAINERS, true)) {
            foreach (ContentModel::findPublishedByPidAndTable((int) $element->id, 'tl_content') ?? [] as $child) {
                array_push($blocks, ...$this->forElement($child));
            }
        }

        return $blocks;
    }

    /**
     * Formulare lassen sich nicht nativ abbilden: Button zur Webseite des Artikels, auf der
     * das Formular steht.
     */
    private function form(ContentModel $element): ?array
    {
        $article = $this->articleOf($element);
        if ($article === null) {
            return null;
        }

        $url = $this->format->url('{{link_url::' . (int) $article->pid . '}}');
        if ($url === null) {
            return null;
        }

        $form = FormModel::findByPk((int) $element->form);

        return [
            'type' => 'button',
            'label' => $this->format->text($form?->title) ?? 'Zum Formular',
            'url' => $url,
            'external' => true,
        ];
    }

    private function articleOf(ContentModel $element): ?ArticleModel
    {
        for ($i = 0; $i < 10 && $element->ptable === 'tl_content'; $i++) {
            $element = ContentModel::findByPk((int) $element->pid);
            if ($element === null) {
                return null;
            }
        }

        return $element->ptable === 'tl_article' ? ArticleModel::findByPk((int) $element->pid) : null;
    }

    public function download(mixed $uuid, ?string $label): ?array
    {
        $file = \is_string($uuid) && $uuid !== '' ? FilesModel::findByUuid($uuid) : null;
        if ($file === null) {
            return null;
        }

        $mime = null;
        $size = null;
        try {
            $info = new File($file->path);
            $mime = $info->mime ?: null;
            $size = $info->size ?: null;
        } catch (\Throwable) {
            // Datei fehlt auf dem Server: Link wird trotzdem geliefert
        }

        return [
            'type' => 'download',
            'label' => $this->format->text($label) ?? basename($file->path),
            'url' => $this->images->absolute($file->path),
            'mimeType' => $mime,
            'size' => $size !== null ? (int) $size : null,
        ];
    }

    public function video(string $provider, string $id, ?string $title): ?array
    {
        $id = trim($id);

        return $id === '' ? null : [
            'type' => 'video',
            'provider' => $provider === 'vimeo' ? 'vimeo' : 'youtube',
            'id' => $id,
            'title' => $this->format->text($title),
        ];
    }

    private function headline(ContentModel $element): ?array
    {
        $headline = StringUtil::deserialize($element->headline, true);
        $text = $this->format->text($headline['value'] ?? null);

        if ($text === null) {
            return null;
        }

        $level = (int) substr((string) ($headline['unit'] ?? 'h2'), 1);

        return ['type' => 'headline', 'text' => $text, 'level' => $level >= 1 && $level <= 6 ? $level : 2];
    }

    private function text(ContentModel $element): ?array
    {
        $html = $this->format->html($element->text);

        return $html === null ? null : ['type' => 'text', 'html' => $html];
    }

    private function image(ContentModel $element): ?array
    {
        $image = $this->images->fromUuid(
            $element->singleSRC,
            1000,
            null,
            $this->format->text($element->alt),
            $this->format->text($element->caption),
        );

        return $image === null ? null : [
            'type' => 'image',
            'image' => $image,
            'linkUrl' => $this->format->url($element->imageUrl),
        ];
    }

    private function list(ContentModel $element): ?array
    {
        $items = [];
        foreach (StringUtil::deserialize($element->listitems, true) as $item) {
            if (($text = $this->format->text($item)) !== null) {
                $items[] = $text;
            }
        }

        return $items === [] ? null : ['type' => 'list', 'ordered' => $element->listtype === 'ordered', 'items' => $items];
    }

    private function button(ContentModel $element): ?array
    {
        $url = $this->format->url($element->url);
        if ($url === null) {
            return null;
        }

        return [
            'type' => 'button',
            'label' => $this->format->text($element->linkTitle) ?? $url,
            'url' => $url,
            'external' => !str_starts_with($url, 'app://'),
        ];
    }
}
