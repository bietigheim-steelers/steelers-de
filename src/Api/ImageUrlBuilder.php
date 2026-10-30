<?php

namespace App\Api;

use Contao\CoreBundle\Image\Studio\Studio;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Macht aus einer Contao-Datei-UUID (binary(16) oder String) ein Bildobjekt der API mit absoluter URL.
 * Die Groessen werden hier festgelegt und brauchen keinen Eintrag in config/config.yml.
 */
class ImageUrlBuilder
{
    public function __construct(
        #[Autowire(service: 'contao.image.studio')]
        private readonly Studio $studio,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @return array{url: string, width: int|null, height: int|null, alt: string|null, caption: string|null}|null
     */
    public function fromUuid(mixed $uuid, int $width = 800, ?int $height = null, ?string $alt = null, ?string $caption = null): ?array
    {
        if (!\is_string($uuid) || $uuid === '') {
            return null;
        }

        $size = [$width, $height ?? 0, $height ? 'crop' : 'proportional'];

        try {
            $figure = $this->studio->createFigureBuilder()->from($uuid)->setSize($size)->buildIfResourceExists();
        } catch (\Throwable) {
            return null;
        }

        if ($figure === null) {
            return null;
        }

        $img = $figure->getImage()->getImg();
        $src = (string) ($img['src'] ?? '');
        if ($src === '') {
            return null;
        }

        return [
            'url' => $this->absolute($src),
            'width' => isset($img['width']) ? (int) $img['width'] : null,
            'height' => isset($img['height']) ? (int) $img['height'] : null,
            'alt' => $alt !== null && $alt !== '' ? $alt : null,
            'caption' => $caption !== null && $caption !== '' ? $caption : null,
        ];
    }

    /**
     * Macht aus einem (relativen) Pfad eine absolute URL auf diese Installation.
     */
    public function absolute(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        $base = $request ? $request->getSchemeAndHttpHost() : '';

        return $base . '/' . ltrim($path, '/');
    }
}
