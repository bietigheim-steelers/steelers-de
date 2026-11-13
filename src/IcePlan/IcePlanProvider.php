<?php

declare(strict_types=1);

namespace App\IcePlan;

use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Findet die Eisplan-PDFs eines Ordners und liefert den geparsten Inhalt.
 *
 * Dateinamen: Eisplan_KW47-2026b.pdf. Der Buchstabe am Ende ist die Version – je Woche
 * zaehlt die hoechste. Geparst wird erst beim Aufruf; das Ergebnis liegt im Cache und
 * ist an Aenderungsdatum und Groesse der Datei gebunden (neues PDF = neu einlesen).
 */
class IcePlanProvider
{
    private const NAME = '/^Eisplan_KW(\d{1,2})-(\d{4})([a-z]?)\.pdf$/i';
    private const CACHE_VERSION = 2;

    public function __construct(
        private readonly IcePlanParser $parser,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, array{key: string, year: int, week: int, monday: \DateTimeImmutable, path: string, file: string}> nach Datum sortiert, Schluessel "2026-47"
     */
    public function weeks(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $weeks = [];
        foreach ((new Finder())->files()->in($directory)->depth(0)->name(self::NAME) as $file) {
            preg_match(self::NAME, $file->getFilename(), $m);
            $year = (int) $m[2];
            $week = (int) $m[1];
            if ($week < 1 || $week > 53) {
                continue;
            }
            $key = sprintf('%d-%02d', $year, $week);
            $revision = strtolower($m[3]);
            if (isset($weeks[$key]) && $weeks[$key]['revision'] >= $revision) {
                continue;
            }
            $weeks[$key] = [
                'key' => $key,
                'year' => $year,
                'week' => $week,
                'monday' => (new \DateTimeImmutable())->setISODate($year, $week, 1)->setTime(0, 0),
                'path' => $file->getPathname(),
                'file' => $file->getFilename(),
                'revision' => $revision,
            ];
        }
        ksort($weeks);

        return array_map(static function (array $w): array {
            unset($w['revision']);

            return $w;
        }, $weeks);
    }

    /** @return list<array<string, mixed>>|null */
    public function plan(string $path): ?array
    {
        $key = sprintf('iceplan_v%d_%s', self::CACHE_VERSION, md5($path . '|' . filemtime($path) . '|' . filesize($path)));

        return $this->cache->get($key, function (ItemInterface $item) use ($path): ?array {
            $item->expiresAfter(86400 * 30);
            try {
                $days = $this->parser->parseFile($path);
            } catch (\Throwable $e) {
                $this->logger->error('Eisplan konnte nicht gelesen werden: ' . $e->getMessage(), ['file' => $path]);

                return null;
            }
            if ($days === null) {
                $this->logger->error('Eisplan hat nicht das erwartete Raster (Kopfzeile/Tage nicht gefunden).', ['file' => $path]);
            }

            return $days;
        });
    }
}
