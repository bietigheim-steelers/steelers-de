<?php

namespace App\Api;

use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\StringUtil;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Bereitet Texte aus Contao fuer die App auf: Insert-Tags aufloesen, Entities dekodieren,
 * HTML auf wenige Tags reduzieren bzw. in Klartext wandeln.
 */
class ContentFormatter
{
    private readonly HtmlSanitizer $sanitizer;

    public function __construct(
        #[Autowire(service: 'contao.insert_tag.parser')]
        private readonly InsertTagParser $insertTags,
        private readonly ImageUrlBuilder $images,
    ) {
        $config = (new HtmlSanitizerConfig())
            ->allowElement('p')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            ->allowElement('br')
            ->allowElement('a', ['href'])
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks(false);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    /**
     * Klartext mit Absaetzen als Leerzeile.
     */
    public function plain(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = $this->insertTags->replaceInline($html);
        $html = preg_replace('#</p>\s*<p[^>]*>#i', "\n\n", $html);
        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $text = StringUtil::decodeEntities(strip_tags($html));

        // Leerzeichen (auch geschuetzte) zusammenfassen, Zeilen trimmen, mehrere Leerzeilen auf eine
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;
        $lines = array_map('trim', preg_split('/\R/u', $text) ?: []);
        $text = trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)) ?? '');

        return $text === '' ? null : $text;
    }

    /**
     * Kurzer einzeiliger Text (Name, Ort): Entities dekodiert, keine Tags.
     */
    public function text(mixed $value): ?string
    {
        if ($value === null || !\is_scalar($value)) {
            return null;
        }

        $text = trim(StringUtil::decodeEntities(strip_tags((string) $value)));

        return $text === '' ? null : $text;
    }

    /**
     * HTML, reduziert auf p, strong, em, ul, ol, li, br, a (nur absolute Links).
     */
    public function html(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $html = $this->insertTags->replaceInline($html);
        // Relative Links (z. B. aus {{link_url::…}}) vor dem Bereinigen absolut machen
        $html = preg_replace_callback(
            '/href="(?!https?:|mailto:|tel:|#)([^"]*)"/i',
            fn (array $m) => 'href="' . $this->images->absolute(html_entity_decode($m[1])) . '"',
            $html,
        );

        $clean = trim($this->sanitizer->sanitize($html));

        return $clean === '' ? null : $clean;
    }

    /**
     * Zeitstempel als ISO-8601-String in Europe/Berlin.
     */
    public function date(int|string|null $timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        return (new \DateTimeImmutable('@' . (int) $timestamp))
            ->setTimezone(new \DateTimeZone('Europe/Berlin'))
            ->format(\DATE_ATOM);
    }
}
