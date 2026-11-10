<?php

namespace App\Api;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Date;
use Contao\File;
use Contao\FilesModel;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\System;
use Contao\StringUtil;

/**
 * Übersetzt Inhaltselemente der Webseite (tl_content) in App-Bausteine (docs/openapi.yaml: Block).
 * Unterstützt: headline (auch als Überschrift anderer Elemente), text, image, list, hyperlink,
 * download, youtube, vimeo, Formulare (nativ als form, sonst Button zur Webseite) und verschachtelte Gruppen.
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

        // Bild am Textelement ("Bild hinzufügen"): als eigener Bild-Baustein vor dem Text
        if ($element->type === 'text' && $element->addImage && ($image = $this->image($element)) !== null) {
            $blocks[] = $image;
        }

        if ($element->type === 'gallery') {
            array_push($blocks, ...$this->gallery($element));
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
     * Formulare werden nativ als `form`-Block geliefert: Feldbeschreibung plus die Seite des Artikels,
     * an die die App per POST sendet (wie der Browser; Contao verarbeitet es wie gewohnt).
     * Enthält das Formular etwas, das die App nicht darstellen kann (Upload, Captcha, HTML, GET-Formular,
     * dynamische Felder ohne Optionen), bleibt es beim Button zur Webseite.
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
        $title = $this->format->text($form?->title);

        $fields = $form !== null && $form->method !== 'GET' ? $this->formFields($form) : null;
        if ($fields === null) {
            return ['type' => 'button', 'label' => $title ?? 'Zum Formular', 'url' => $url, 'external' => true];
        }

        return [
            'type' => 'form',
            'title' => $title,
            'action' => $url,
            'formSubmit' => $form->formID ? 'auto_' . $form->formID : 'auto_form_' . $form->id,
            'submitLabel' => $fields['submit'],
            'fields' => $fields['fields'],
        ];
    }

    /**
     * Baut die Felder wie Contao\Form (inklusive `loadFormField`-Hooks, die z. B. Optionen dynamisch füllen).
     * `null`, wenn ein Feld nicht darstellbar ist.
     *
     * @return array{fields: list<array>, submit: string}|null
     */
    private function formFields(FormModel $form): ?array
    {
        $models = FormFieldModel::findPublishedByPid((int) $form->id);
        if ($models === null) {
            return null;
        }

        $formObject = new Form($form);
        $formId = $form->formID ? 'auto_' . $form->formID : 'auto_form_' . $form->id;
        $hooks = $GLOBALS['TL_HOOKS']['loadFormField'] ?? [];
        $fields = [];
        $submit = 'Absenden';

        foreach ($models as $model) {
            $type = $model->type;
            $class = $GLOBALS['TL_FFL'][$type] ?? null;

            if (\in_array($type, ['fieldsetStart', 'fieldsetStop'], true)) {
                continue;
            }
            if ($class === null || !class_exists($class) || !\in_array($type, ['text', 'textarea', 'select', 'radio', 'checkbox', 'hidden', 'explanation', 'submit'], true)) {
                return null;
            }

            $data = $model->row();
            $data['decodeEntities'] = true;
            if ($type === 'submit') {
                $data['name'] = '';
            }
            $widget = new $class($data);
            $widget->required = $model->mandatory;
            foreach ($hooks as $callback) {
                $widget = System::importStatic($callback[0])->{$callback[1]}($widget, $formId, $form->row(), $formObject);
            }

            if ($type === 'submit') {
                $submit = $this->format->text($model->slabel) ?? $submit;
                continue;
            }

            $label = $this->format->text($widget->label);

            if ($type === 'explanation') {
                $html = $this->format->html($model->text);
                if ($html !== null) {
                    $fields[] = ['type' => 'explanation', 'html' => $html];
                }
                continue;
            }

            $name = (string) $model->name;
            if ($name === '') {
                return null;
            }

            $field = [
                'name' => $name,
                'label' => $label ?? $name,
                'type' => $type,
                'required' => (bool) $model->mandatory,
                'value' => $this->format->text($model->value),
                'placeholder' => $this->format->text($model->placeholder),
            ];

            if ($type === 'text') {
                $field['type'] = match ((string) $model->rgxp) {
                    'email' => 'email',
                    'digit', 'natural', 'prcnt' => 'number',
                    'phone' => 'phone',
                    'date' => 'date',
                    default => 'text',
                };
            } elseif (\in_array($type, ['select', 'radio', 'checkbox'], true)) {
                $options = [];
                foreach ((array) $widget->options as $option) {
                    if (!empty($option['group'])) {
                        continue;
                    }
                    $options[] = [
                        'value' => (string) ($option['value'] ?? ''),
                        'label' => $this->format->text($option['label'] ?? null) ?? (string) ($option['value'] ?? ''),
                        'default' => !empty($option['default']),
                    ];
                }
                if ($options === []) {
                    return null;
                }
                $field['options'] = $options;
                $field['multiple'] = $type === 'checkbox' ? \count($options) > 1 : (bool) $model->multiple;
            }

            $fields[] = $field;
        }

        return ['fields' => $fields, 'submit' => $submit];
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

    /**
     * Galerie: ein Bild-Baustein je Datei (Ordner werden aufgelöst), in der im Backend gesetzten Reihenfolge.
     *
     * @return list<array>
     */
    private function gallery(ContentModel $element): array
    {
        $uuids = StringUtil::deserialize($element->multiSRC, true);
        if ($uuids === []) {
            return [];
        }

        $files = FilesModel::findMultipleByUuids($uuids);
        $paths = [];
        foreach ($files ?? [] as $file) {
            if ($file->type === 'folder') {
                foreach (FilesModel::findByPid($file->uuid) ?? [] as $child) {
                    if ($child->type === 'file') {
                        $paths[$child->uuid] = $child;
                    }
                }
            } else {
                $paths[$file->uuid] = $file;
            }
        }

        $order = array_flip(array_map('strval', StringUtil::deserialize($element->orderSRC, true)));
        $models = array_values($paths);
        usort($models, static function ($a, $b) use ($order) {
            $ia = $order[$a->uuid] ?? PHP_INT_MAX;
            $ib = $order[$b->uuid] ?? PHP_INT_MAX;

            return $ia === $ib ? strcmp($a->path, $b->path) : $ia <=> $ib;
        });

        $blocks = [];
        foreach ($models as $file) {
            $meta = StringUtil::deserialize($file->meta, true)['de'] ?? [];
            $image = $this->images->fromUuid(
                $file->uuid,
                1000,
                null,
                $this->format->text($meta['alt'] ?? null),
                $this->format->text($meta['caption'] ?? null),
            );
            if ($image !== null) {
                $blocks[] = ['type' => 'image', 'image' => $image, 'linkUrl' => null];
            }
        }

        return $blocks;
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
