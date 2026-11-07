<?php

namespace App\Seats;

/**
 * Kennt den tatsaechlichen Hallenbestand und beantwortet, ob ein Platz
 * existiert und ob sein Block ueberhaupt als Dauerkarte verkauft wird.
 *
 * Der Bestand lag bisher nur im Frontend (assets/js/seats.json). Der Server
 * hat Block, Reihe und Platz deshalb ungeprueft uebernommen und nicht
 * vorhandene Plaetze bei Bedarf neu angelegt.
 *
 * ACHTUNG: Bis das Formular seine Konfiguration ueber #dk-config bekommt,
 * liegt dieselbe Datei ein zweites Mal unter assets/js/seats.json, weil die
 * Vue-Komponenten sie direkt importieren. Aenderungen am Hallenplan muessen
 * bis dahin in beide Dateien.
 */
class SeatInventory
{
    /**
     * Bloecke, die im Hallenplan stehen, aber kein Dauerkartenbereich sind.
     * E ist der Gaestefanblock.
     */
    private const NON_SELLABLE_BLOCKS = ['E'];

    /**
     * Rollstuhlplaetze stehen nicht im Hallenplan, es gibt nur ihre Anzahl.
     * Reihe ist dort immer 1.
     */
    private const WHEELCHAIR_CAPACITY = [
        'R1' => 2,
        'R3' => 3,
        'R4' => 3,
    ];

    private ?array $sections = null;

    /**
     * Ein Sitzplatzblock, der als Dauerkarte verkauft wird.
     */
    public function isSeatedBlock(string $block): bool
    {
        if (in_array($block, self::NON_SELLABLE_BLOCKS, true)) {
            return false;
        }

        return isset($this->sections()[$block]);
    }

    /**
     * Anzahl echter Plaetze einer Reihe: Rasterplaetze minus Gangpositionen.
     * null, wenn Block oder Reihe nicht existieren.
     */
    public function seatCount(string $block, string $row): ?int
    {
        if (!$this->isSeatedBlock($block)) {
            return null;
        }

        $rows = $this->sections()[$block]['rows'] ?? [];

        if (!isset($rows[$row]['seats'])) {
            return null;
        }

        $skipped = is_array($rows[$row]['skip'] ?? null) ? count($rows[$row]['skip']) : 0;

        return max(0, (int) $rows[$row]['seats'] - $skipped);
    }

    public function seatExists(string $block, string $row, int $seat): bool
    {
        $count = $this->seatCount($block, $row);

        return $count !== null && $seat >= 1 && $seat <= $count;
    }

    /**
     * Pruefen, dass ein ganzer Platzblock (Familienkarte) in dieselbe Reihe passt.
     */
    public function seatRangeExists(string $block, string $row, int $firstSeat, int $seats): bool
    {
        if ($seats < 1) {
            return false;
        }

        $count = $this->seatCount($block, $row);

        return $count !== null && $firstSeat >= 1 && ($firstSeat + $seats - 1) <= $count;
    }

    public function isWheelchairBlock(string $block): bool
    {
        return isset(self::WHEELCHAIR_CAPACITY[$block]);
    }

    public function wheelchairCapacity(string $block): ?int
    {
        return self::WHEELCHAIR_CAPACITY[$block] ?? null;
    }

    /**
     * @return list<string>
     */
    public function sellableSeatedBlocks(): array
    {
        return array_values(array_filter(
            array_keys($this->sections()),
            fn (string $block): bool => !in_array($block, self::NON_SELLABLE_BLOCKS, true)
        ));
    }

    /**
     * Die Blockdaten samt Geometrie fuer den Saalplan im Frontend --
     * ohne die Bloecke, die nicht verkauft werden.
     *
     * @return array<string, array<string, mixed>>
     */
    public function sellableSections(): array
    {
        return array_diff_key($this->sections(), array_flip(self::NON_SELLABLE_BLOCKS));
    }

    /**
     * @return array<string, int>
     */
    public function wheelchairBlocks(): array
    {
        return self::WHEELCHAIR_CAPACITY;
    }

    private function sections(): array
    {
        if ($this->sections !== null) {
            return $this->sections;
        }

        $path = __DIR__ . '/seats.json';
        $raw = is_readable($path) ? file_get_contents($path) : false;

        if ($raw === false) {
            throw new \RuntimeException('Hallenplan nicht lesbar: ' . $path);
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded) || !is_array($decoded['sections'] ?? null)) {
            throw new \RuntimeException('Hallenplan enthaelt keine sections: ' . $path);
        }

        return $this->sections = $decoded['sections'];
    }
}
