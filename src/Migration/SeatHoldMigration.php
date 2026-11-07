<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalten für die befristete Platzreservierung an.
 *
 * Bisher fiel ein Sitzplatzkonflikt erst beim Absenden des gesamten
 * Formulars auf. Mit reserved_until und hold_token wird ein Platz schon bei
 * der Auswahl gehalten, und der Besucher sieht einen Countdown.
 *
 * Der Ablauf eines Holds ist bewusst ein Query-Prädikat und kein Cronjob:
 * belegt ist, was booked ist oder was als hold mit reserved_until in der
 * Zukunft steht. Damit stimmt der Zustand auch dann, wenn Contaos
 * request-getriebener Cron gerade nicht läuft.
 *
 * Wie die übrigen Migrationen hier als Migration und nicht über den
 * Schema-Abgleich, weil der auf diesem Datenbestand zahlreiche Alt-Spalten
 * entfernter Extensions löschen würde (siehe AGENTS.md). Ausrollen mit
 * "contao:migrate --migrations-only".
 */
class SeatHoldMigration extends AbstractMigration
{
    private const TABLE = 'tl_tilastot_seats';

    /**
     * Spaltenname => DDL.
     */
    private const COLUMNS = [
        'reserved_until' => "ADD reserved_until INT UNSIGNED DEFAULT 0 NOT NULL",
        'hold_token' => "ADD hold_token VARCHAR(64) DEFAULT '' NOT NULL",
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return [] !== $this->getMissingColumns();
    }

    public function run(): MigrationResult
    {
        $missing = $this->getMissingColumns();

        if ([] === $missing) {
            return $this->createResult(true, 'No columns were missing in ' . self::TABLE . '.');
        }

        $this->connection->executeStatement(
            'ALTER TABLE ' . self::TABLE . ' ' . implode(', ', array_values($missing))
        );

        return $this->createResult(
            true,
            'Added ' . implode(', ', array_keys($missing)) . ' to ' . self::TABLE . '.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMissingColumns(): array
    {
        // Bewusst ueber information_schema und nicht ueber den SchemaManager:
        // Contao filtert die Introspektion, und tl_tilastot_seats kam dort
        // nicht an -- die Migration haette sich dann nie fuer zustaendig
        // gehalten.
        $existing = $this->connection->fetchFirstColumn(
            'SELECT LOWER(COLUMN_NAME) FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [self::TABLE]
        );

        if ([] === $existing) {
            return []; // Tabelle gibt es (noch) nicht
        }

        $existing = array_flip($existing);

        return array_filter(
            self::COLUMNS,
            static fn (string $column): bool => !isset($existing[strtolower($column)]),
            ARRAY_FILTER_USE_KEY
        );
    }
}
