<?php

namespace App\SeasonTicket;

/**
 * Preise und Kategorien der Dauerkarte an genau einer Stelle.
 *
 * Vorher lag die Tabelle doppelt (PHP in SeasonTicketController, JS in
 * assets/js/TicketPriceCalculator.js) und die Kategorie-Labels dreifach.
 * Jetzt ist PHP die Quelle; das Frontend bekommt sie ueber FormConfig
 * als JSON mit auf die Seite.
 */
class PriceTable
{
    public const TYPES = ['plus', 'basic'];

    public const AREAS = ['stehplatz', 'sitzplatz', 'rollstuhl'];

    /** Kategorien, die zum selben Preis wie "ermaessigt" gefuehrt werden. */
    public const REDUCED_CATEGORIES = ['rentner', 'student', 'azubi', 'schueler', 'mitglied'];

    /** Sitzplatzblock -> Preisgruppe. */
    public const BLOCK_GROUPS = [
        'A' => 'A,G',
        'G' => 'A,G',
        'B' => 'B,F,H,L',
        'F' => 'B,F,H,L',
        'H' => 'B,F,H,L',
        'L' => 'B,F,H,L',
        'C' => 'C,I,K',
        'I' => 'C,I,K',
        'K' => 'C,I,K',
    ];

    /** Preisgruppen, die nur ueber den Bereich erreichbar sind. */
    public const STANDING_GROUP = 'J';
    public const WHEELCHAIR_GROUP = 'R1,R3,R4';

    /** Familienkarten gibt es nur in dieser Preisgruppe. */
    public const FAMILY_GROUP = 'C,I,K';

    public const FAMILY_CATEGORIES = ['familie1', 'familie2', 'familie3'];

    /**
     * Wie viele Plaetze eine Kategorie belegt.
     */
    public const SEATS_PER_CATEGORY = [
        'familie1' => 3,
        'familie2' => 3,
        'familie3' => 4,
    ];

    private const PRICES = [
        'plus' => [
            'A,G' => [
                'vollzahler' => 855,
                'ermaessigt' => 735,
                'jugendlich' => 510,
                'kind' => 426,
                'behinderung' => 426,
            ],
            'B,F,H,L' => [
                'vollzahler' => 737,
                'ermaessigt' => 618,
                'jugendlich' => 453,
                'kind' => 369,
                'behinderung' => 369,
            ],
            'C,I,K' => [
                'vollzahler' => 621,
                'ermaessigt' => 537,
                'jugendlich' => 360,
                'kind' => 310,
                'behinderung' => 310,
                'familie1' => 948,
                'familie2' => 1249,
                'familie3' => 1435,
            ],
            'J' => [
                'vollzahler' => 430,
                'ermaessigt' => 381,
                'jugendlich' => 275,
                'kind' => 215,
                'behinderung' => 215,
            ],
            'R1,R3,R4' => [
                'rollstuhl' => 383,
            ],
        ],
        'basic' => [
            'A,G' => [
                'vollzahler' => 749,
                'ermaessigt' => 645,
                'jugendlich' => 447,
                'kind' => 375,
                'behinderung' => 375,
            ],
            'B,F,H,L' => [
                'vollzahler' => 645,
                'ermaessigt' => 540,
                'jugendlich' => 395,
                'kind' => 322,
                'behinderung' => 322,
            ],
            'C,I,K' => [
                'vollzahler' => 540,
                'ermaessigt' => 468,
                'jugendlich' => 312,
                'kind' => 270,
                'behinderung' => 270,
                'familie1' => 832,
                'familie2' => 1102,
                'familie3' => 1268,
            ],
            'J' => [
                'vollzahler' => 374,
                'ermaessigt' => 333,
                // Krumme Werte, aber so vorgegeben. Frueher schnitt das
                // ": int" der PHP-Methoden sie auf 129 ab, waehrend das
                // Frontend auf 130 gerundet anzeigte.
                'jugendlich' => 129.74,
                'kind' => 129.74,
                'behinderung' => 187,
            ],
            'R1,R3,R4' => [
                'rollstuhl' => 333,
            ],
        ],
    ];

    /**
     * @return array<string, array<string, array<string, int|float>>>
     */
    public static function prices(): array
    {
        return self::PRICES;
    }

    /**
     * @return array<string, string>
     */
    public static function categoryLabels(): array
    {
        return [
            'vollzahler' => 'Vollzahler',
            'familie1' => 'Familienkarte 1',
            'familie2' => 'Familienkarte 2',
            'familie3' => 'Familienkarte 3',
            'rentner' => 'Rentner',
            'student' => 'Student',
            'azubi' => 'Auszubildender',
            'schueler' => 'Schüler über 18 Jahre',
            'mitglied' => 'SC Mitglied',
            'jugendlich' => 'Jugendlicher (13-17 Jahre)',
            'kind' => 'Kind (8-12 Jahre)',
            'behinderung' => 'Fan mit Behinderung ab 50%',
            'rollstuhl' => 'Rollstuhlfahrer inkl. Begleitperson',
        ];
    }

    /**
     * Kategorien, die im Formular zur Auswahl stehen, in Anzeigereihenfolge.
     * rollstuhl und die Familienkarten haengen am Bereich und stehen nicht
     * in der Grundliste.
     *
     * @return list<string>
     */
    public static function selectableCategories(): array
    {
        return [
            'vollzahler',
            'rentner',
            'student',
            'azubi',
            'schueler',
            'mitglied',
            'jugendlich',
            'kind',
            'behinderung',
        ];
    }

    /**
     * Wie viele Plaetze eine Bestellung dieser Kategorie belegt.
     */
    public static function seatsFor(?string $category): int
    {
        return self::SEATS_PER_CATEGORY[$category] ?? 1;
    }

    /**
     * Preis in Euro. 0.0, wenn die Kombination nicht existiert -- geprueft
     * wird sie vorher im Controller.
     */
    public static function priceFor(?string $type, ?string $category, ?string $area, ?string $block): float
    {
        if ($type === null || !isset(self::PRICES[$type])) {
            return 0.0;
        }

        $category = in_array($category, self::REDUCED_CATEGORIES, true) ? 'ermaessigt' : $category;

        $group = match ($area) {
            'stehplatz' => self::STANDING_GROUP,
            'rollstuhl' => self::WHEELCHAIR_GROUP,
            default => self::BLOCK_GROUPS[$block] ?? null,
        };

        if ($area === 'rollstuhl') {
            $category = 'rollstuhl';
        }

        if ($group === null || $category === null) {
            return 0.0;
        }

        return (float) (self::PRICES[$type][$group][$category] ?? 0);
    }
}
