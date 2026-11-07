<?php

namespace App\SeasonTicket;

use App\Seats\SeatInventory;

/**
 * Alles, was das Bestellformular im Browser wissen muss, an einer Stelle.
 *
 * Das Ergebnis landet als <script type="application/json" id="dk-config">
 * in der Seite. Dadurch gibt es keine zweite Preistabelle, keine zweite
 * Kopie des Hallenplans und keine Datumsgrenzen mehr, die in einer
 * Vue-Komponente vergammeln -- genau daran ist die Family-+-Friends-Aktion
 * ein Jahr lang unbemerkt gescheitert.
 *
 * Alles unter "season", "salePhases" und "features" ist das, was sich
 * jede Saison aendert.
 */
class FormConfig
{
    private const SEASON = '2026/2027';

    private const PREVIOUS_SEASON = '2025/2026';

    public function __construct(private readonly SeatInventory $inventory)
    {
    }

    public function toJson(): string
    {
        return json_encode(
            $this->toArray(),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'season' => self::SEASON,
            'previousSeason' => self::PREVIOUS_SEASON,

            'types' => [
                [
                    'value' => 'plus',
                    'label' => 'Dauerkarte PLUS',
                    'description' => 'Alle Vorbereitungsspiele + alle Hauptrundenspiele'
                        . '<br>+ alle möglichen Spiele in den Pre-Playoffs/Playoffs/Playdowns',
                ],
                [
                    'value' => 'basic',
                    'label' => 'Dauerkarte BASIC',
                    'description' => 'Alle Hauptrundenspiele',
                ],
            ],

            'areas' => [
                ['value' => 'stehplatz', 'label' => 'Stehplatz'],
                ['value' => 'sitzplatz', 'label' => 'Sitzplatz'],
                ['value' => 'rollstuhl', 'label' => 'Rollstuhlfahrer'],
            ],

            'prices' => PriceTable::prices(),
            'blockGroups' => PriceTable::BLOCK_GROUPS,
            'standingGroup' => PriceTable::STANDING_GROUP,
            'wheelchairGroup' => PriceTable::WHEELCHAIR_GROUP,
            'familyGroup' => PriceTable::FAMILY_GROUP,
            'familyCategories' => PriceTable::FAMILY_CATEGORIES,
            'seatsPerCategory' => PriceTable::SEATS_PER_CATEGORY,
            'reducedCategories' => PriceTable::REDUCED_CATEGORIES,
            'categoryLabels' => PriceTable::categoryLabels(),
            'selectableCategories' => PriceTable::selectableCategories(),

            'sections' => $this->inventory->sellableSections(),
            'wheelchairBlocks' => $this->inventory->wheelchairBlocks(),

            'ticketForms' => [
                ['value' => 'plastik', 'label' => 'Plastikkarte'],
                ['value' => 'mobile_plastik', 'label' => 'Plastikkarte + mobile Dauerkarte'],
                ['value' => 'mobile', 'label' => 'Nur mobile Dauerkarte'],
            ],

            'payment' => [
                'options' => [
                    [
                        'value' => 'ueberweisung',
                        'label' => 'Überweisung',
                        'description' => 'Überweisung auf das Konto der Steelers GmbH'
                            . '<br>Kreissparkasse Ludwigsburg, IBAN: DE91 6045 0050 0030 2168 19',
                    ],
                    [
                        'value' => 'gs',
                        'label' => 'Bezahlung auf der Steelers-Geschäftsstelle (in bar oder mit EC-Karte)',
                    ],
                ],
                'note' => 'Die Ausgabe der Dauerkarte (schätzungsweise ab Mitte August)'
                    . ' erfolgt nur bei vorheriger Bezahlung.',
            ],

            'salePhases' => [
                ['label' => '1. Phase', 'from' => '22.04.2026', 'to' => '22.05.2026', 'paymentBy' => '22.05.2026'],
                ['label' => '2. Phase', 'from' => '23.05.2026', 'to' => '31.08.2026', 'paymentBy' => '31.08.2026'],
            ],

            // Schaltet Funktionen, die nicht jede Saison laufen. Frueher stand
            // so etwas als v-if mit hartem Datum in der Komponente.
            'features' => [
                'familyAndFriends' => false,
            ],

            'endpoints' => [
                'order' => '/seasonticket/order',
                'seats' => '/seasontickets/api/seats',
                'hold' => '/seasontickets/api/hold',
                'release' => '/seasontickets/api/release',
            ],

            'holdMinutes' => 15,
        ];
    }
}
