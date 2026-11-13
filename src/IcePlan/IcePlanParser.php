<?php

namespace App\IcePlan;

use Smalot\PdfParser\Parser;

/**
 * Liest den woechentlichen Eisplan (PDF-Raster, Tage nebeneinander, je Tag die
 * Spalten "Arena" und "Tr. Halle") und macht daraus eine Liste von Eintraegen.
 *
 * Das PDF hat keine Tabellenstruktur, nur Textstuecke mit Position. Deshalb:
 *  1. Kopfzeile (Datum + "Arena"/"Tr. Halle") legt die 14 Spalten fest.
 *  2. Jedes Textstueck wird ueber seine geschaetzte Mitte einer Spalte zugeordnet
 *     (Zellen sind zentriert, die PDF-Koordinate ist aber der linke Rand).
 *  3. Je Spalte beginnt ein Eintrag bei jeder Uhrzeit ("17:30-18:45", "12:15 W-up"),
 *     die Zeilen darunter gehoeren dazu.
 */
class IcePlanParser
{
    private const WEEKDAYS = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

    /** Mitte der ersten Spalte und Abstand der Spalten (14 Spalten, Arena/Halle im Wechsel). */
    private const FIRST_CENTER = 90.0;
    private const COLUMN_WIDTH = 50.0;
    /** Mittlere Zeichenbreite zur Schaetzung der Textmitte. */
    private const CHAR_WIDTH = 3.15;

    private const TIME_RANGE = '/^(\d{1,2})[:.](\d{2})\s*[-–]\s*(\d{1,2})[:.](\d{2})$/u';
    private const TIME_ONLY = '/^(\d{1,2}):(\d{2})$/';
    private const TIME_LABEL = '/^(\d{1,2}):(\d{2})\s+(\S.*)$/u';
    private const DATE = '/^(\d{2})\.(\d{2})\.(\d{4})$/';
    private const ROOM = '/^K-/u';
    /** Belegungen, die kein Training eines Teams sind. */
    private const OTHER_USE = '/eislauf|disco|handball|therapie|vermietung|schule|kurs|event/iu';

    public function parseFile(string $path): ?array
    {
        $pages = (new Parser())->parseFile($path)->getPages();
        if ($pages === []) {
            return null;
        }

        return $this->parseFragments($this->fragments($pages[0]), $this->orangeRects($pages[0]));
    }

    /** @return list<array{x: float, y: float, text: string}> */
    private function fragments($page): array
    {
        $out = [];
        foreach ($page->getDataTm() as [$matrix, $text]) {
            $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
            if ($text === '') {
                continue;
            }
            $out[] = ['x' => (float) $matrix[4], 'y' => (float) $matrix[5], 'text' => $text];
        }

        return $out;
    }

    /** @param list<array{x: float, y: float, text: string}> $fragments */
    public function parseFragments(array $fragments, array $orangeRects = []): ?array
    {
        [$headerY, $footerY] = $this->headerRows($fragments);
        $dates = [];
        foreach ($fragments as $f) {
            if (preg_match(self::DATE, $f['text'], $m)) {
                $col = $this->column($f);
                $dates[intdiv($col, 2)] = sprintf('%s-%s-%s', $m[3], $m[2], $m[1]);
            }
        }
        if ($headerY === null || count($dates) !== 7) {
            return null;
        }
        ksort($dates);

        // Spalten sammeln, Zeitachse links/rechts (Beschriftungen) und Kopf/Fuss ausblenden.
        $columns = array_fill(0, 14, []);
        foreach ($fragments as $f) {
            if ($f['y'] >= $headerY - 3 || $f['y'] <= $footerY + 3) {
                continue;
            }
            $center = $f['x'] + strlen($f['text']) * self::CHAR_WIDTH / 2;
            if ($center < self::FIRST_CENTER - 30 || $center > self::FIRST_CENTER + 13 * self::COLUMN_WIDTH + 30) {
                continue;
            }
            $columns[$this->column($f)][] = $f;
        }

        $days = [];
        foreach ($dates as $dayIndex => $date) {
            $entries = [];
            foreach ([0 => 'arena', 1 => 'halle'] as $offset => $place) {
                foreach ($this->entries($columns[$dayIndex * 2 + $offset], $place, $orangeRects) as $entry) {
                    $entries[] = $entry;
                }
            }
            usort($entries, static fn (array $a, array $b): int => [$a['start'], $a['place']] <=> [$b['start'], $b['place']]);
            $days[] = ['date' => $date, 'weekday' => self::WEEKDAYS[$dayIndex], 'entries' => $entries];
        }

        return $days;
    }

    /**
     * y-Koordinaten der Kopfzeile ("Arena"/"Tr. Halle" oben) und der Fusszeile (dieselbe
     * Beschriftung unten). Dazwischen steht der Plan.
     *
     * @return array{0: ?float, 1: float}
     */
    private function headerRows(array $fragments): array
    {
        $ys = [];
        foreach ($fragments as $f) {
            if ($f['text'] === 'Tr. Halle') {
                $ys[] = $f['y'];
            }
        }

        return $ys === [] ? [null, 0.0] : [max($ys), count($ys) > 1 ? min($ys) : 0.0];
    }

    private function column(array $f): int
    {
        $center = $f['x'] + strlen($f['text']) * self::CHAR_WIDTH / 2;

        return max(0, min(13, (int) round(($center - self::FIRST_CENTER) / self::COLUMN_WIDTH)));
    }

    /**
     * @param list<array{x: float, y: float, text: string}> $fragments
     *
     * @return list<array<string, mixed>>
     */
    private function entries(array $fragments, string $place, array $orangeRects = []): array
    {
        usort($fragments, static fn (array $a, array $b): int => $b['y'] <=> $a['y']);

        // "Warm" und "up" stehen im PDF teils in getrennten Zeilen.
        $merged = [];
        foreach ($fragments as $f) {
            $last = count($merged) - 1;
            if ($last >= 0 && strcasecmp($f['text'], 'up') === 0 && strcasecmp($merged[$last]['text'], 'Warm') === 0) {
                $merged[$last]['text'] = 'Warm up';
            } else {
                $merged[] = $f;
            }
        }
        $fragments = $merged;

        $blocks = [];
        $current = null;
        foreach ($fragments as $f) {
            $text = $f['text'];

            if (preg_match(self::TIME_RANGE, $text, $m)) {
                $this->flush($blocks, $current);
                $current = ['start' => $this->time($m[1], $m[2]), 'end' => $this->time($m[3], $m[4]), 'warmup' => null, 'lines' => [], 'pos' => [$f['x'], $f['y']]];
            } elseif (preg_match(self::TIME_LABEL, $text, $m) && preg_match('/^(w-?up|warm ?up)/i', $m[3])) {
                // "12:15 W-up": Beginn eines Spiels, die Anspielzeit folgt als eigene Zeile.
                $this->flush($blocks, $current);
                $current = ['start' => $this->time($m[1], $m[2]), 'end' => null, 'warmup' => $this->time($m[1], $m[2]), 'lines' => [], 'pos' => [$f['x'], $f['y']], 'awaitStart' => true];
            } elseif (preg_match(self::TIME_ONLY, $text, $m)) {
                if ($current !== null && ($current['awaitStart'] ?? false)) {
                    $current['start'] = $this->time($m[1], $m[2]);
                    unset($current['awaitStart']);
                } else {
                    $this->flush($blocks, $current);
                    $current = ['start' => $this->time($m[1], $m[2]), 'end' => null, 'warmup' => null, 'lines' => [], 'pos' => [$f['x'], $f['y']]];
                }
            } elseif ($current !== null && $current['lines'] === [] && $current['end'] === null && !$current['warmup'] && preg_match('/^(w-?up|warm ?up)$/i', $text)) {
                // "18:30" und darunter "Warm up": die Uhrzeit war der Beginn des Aufwaermens.
                $current['warmup'] = $current['start'];
                $current['awaitStart'] = true;
            } elseif ($current !== null) {
                $current['lines'][] = $text;
            }
        }
        $this->flush($blocks, $current);

        return array_map(fn (array $b): array => $this->entry($b, $place, $orangeRects), $blocks);
    }

    private function flush(array &$blocks, ?array &$current): void
    {
        if ($current !== null) {
            unset($current['awaitStart']);
            $blocks[] = $current;
        }
        $current = null;
    }

    /** Zeilen eines Blocks in Team/Titel, Gegner und Kabinen aufteilen. */
    private function entry(array $block, string $place, array $orangeRects = []): array
    {
        $title = [];
        $rooms = [];
        $note = [];
        foreach ($block['lines'] as $line) {
            if (preg_match(self::ROOM, $line)) {
                $rooms[] = $line;
            } elseif ($rooms === []) {
                $title[] = $line;
            } else {
                $note[] = $line;
            }
        }

        $type = 'training';
        $opponent = null;
        if ($title !== [] && preg_match('/^Spiel$/iu', $title[0])) {
            $type = 'game';
            array_shift($title);
            $vs = array_search('vs', array_map('strtolower', $title), true);
            if ($vs !== false) {
                $opponent = trim(implode(' ', array_slice($title, $vs + 1)));
                $title = array_slice($title, 0, $vs);
            }
        } elseif ($rooms === [] && preg_match(self::OTHER_USE, implode(' ', $title))) {
            $type = 'other';
        }

        // "K-3, K-5" und "K-1 und K-4" in einzelne Kabinen zerlegen.
        $split = [];
        foreach ($rooms as $room) {
            foreach (preg_split('/\s*(?:,|\bund\b|&)\s*/u', $room) as $part) {
                if ($part !== '') {
                    $split[] = $part;
                }
            }
        }

        return [
            'place' => $place,
            'type' => $type,
            'start' => $block['start'],
            'end' => $block['end'],
            'warmup' => $block['warmup'],
            'title' => trim(implode(' ', $title)),
            'opponent' => $opponent,
            'rooms' => $split,
            // Orange hinterlegt + Kabinen = Hobbymannschaft (Veranstaltungen wie Eislauf haben keine Kabine).
            'hobby' => $split !== [] && $this->inRects($block['pos'] ?? null, $orangeRects),
            'note' => trim(implode(' ', $note)),
        ];
    }

    /**
     * Orange gefuellte Rechtecke der Seite (Hobbymannschaften, Veranstaltungen). Gelesen wird die
     * Fuellfarbe aus dem Seiteninhalt (Excel-Orange 0.969 0.588 0.275).
     *
     * @return list<array{0: float, 1: float, 2: float, 3: float}> x, y, Breite, Hoehe
     */
    private function orangeRects($page): array
    {
        try {
            $contents = $page->get('Contents');
            $stream = method_exists($contents, 'getContent') ? (string) $contents->getContent() : '';
            if ($stream === '' && method_exists($contents, 'getElements')) {
                foreach ($contents->getElements() as $element) {
                    $stream .= $element->getContent() . "\n";
                }
            }
        } catch (\Throwable) {
            return [];
        }

        preg_match_all(
            '/(?:([\d.]+) ([\d.]+) ([\d.]+) rg\b)|(?:([\d.]+) g\b)|(?:(-?[\d.]+) (-?[\d.]+) (-?[\d.]+) (-?[\d.]+) re\s+f\*?(?=\s))/',
            $stream,
            $matches,
            PREG_SET_ORDER,
        );

        $orange = false;
        $rects = [];
        foreach ($matches as $m) {
            if (($m[1] ?? '') !== '') {
                $orange = abs((float) $m[1] - 0.969) < 0.01 && abs((float) $m[2] - 0.588) < 0.01 && abs((float) $m[3] - 0.275) < 0.01;
            } elseif (($m[4] ?? '') !== '') {
                $orange = false;
            } elseif ($orange && ($m[5] ?? '') !== '') {
                $rects[] = [(float) $m[5], (float) $m[6], (float) $m[7], (float) $m[8]];
            }
        }

        return $rects;
    }

    /** @param array{0: float, 1: float}|null $pos */
    private function inRects(?array $pos, array $rects): bool
    {
        if ($pos === null) {
            return false;
        }
        foreach ($rects as [$x, $y, $w, $h]) {
            if ($pos[0] >= $x - 1 && $pos[0] <= $x + $w && $pos[1] >= $y - 1 && $pos[1] <= $y + $h + 1) {
                return true;
            }
        }

        return false;
    }

    private function time(string $h, string $m): string
    {
        return sprintf('%02d:%02d', (int) $h, (int) $m);
    }
}
