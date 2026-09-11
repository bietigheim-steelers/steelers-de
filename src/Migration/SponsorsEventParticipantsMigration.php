<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalten für die optionale Teilnehmerbegrenzung eines Sponsor-Events an.
 *
 * Wie bei App\Migration\SponsorsEventTextsMigration als Migration und nicht über den
 * Schema-Abgleich, weil der auf diesem Datenbestand zahlreiche Alt-Spalten entfernter
 * Extensions löschen würde (siehe AGENTS.md). Ausrollen mit
 * "contao:migrate --migrations-only".
 */
class SponsorsEventParticipantsMigration extends AbstractMigration
{
    /**
     * Spaltenname => DDL.
     */
    private const COLUMNS = [
        'limitParticipants' => 'ADD limitParticipants TINYINT(1) DEFAULT 0 NOT NULL',
        'maxParticipants' => 'ADD maxParticipants INT UNSIGNED DEFAULT 0 NOT NULL',
        'participantCount' => 'ADD participantCount INT UNSIGNED DEFAULT 0 NOT NULL',
        'showParticipantCount' => 'ADD showParticipantCount TINYINT(1) DEFAULT 0 NOT NULL',
        'bookedOutText' => 'ADD bookedOutText TEXT DEFAULT NULL',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_sponsors_event'])) {
            return false;
        }

        return [] !== $this->getMissingColumns();
    }

    public function run(): MigrationResult
    {
        $missing = $this->getMissingColumns();

        if ([] === $missing) {
            return $this->createResult(true, 'No columns were missing in tl_sponsors_event.');
        }

        $this->connection->executeStatement(
            'ALTER TABLE tl_sponsors_event '.implode(', ', array_values($missing))
        );

        return $this->createResult(
            true,
            'Added ' . implode(', ', array_keys($missing)) . ' to tl_sponsors_event.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMissingColumns(): array
    {
        // listTableColumns() liefert die Spalten in Kleinschreibung
        $existing = $this->connection->createSchemaManager()->listTableColumns('tl_sponsors_event');

        return array_filter(
            self::COLUMNS,
            static fn (string $column): bool => !isset($existing[strtolower($column)]),
            ARRAY_FILTER_USE_KEY
        );
    }
}
