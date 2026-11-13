<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalte iceplan_folder (Ordner mit den Eisplan-PDFs) am Frontend-Modul an.
 * Als Migration, weil der Schema-Abgleich auf diesem Datenbestand nicht benutzbar ist (siehe AGENTS.md).
 */
class IcePlanModuleMigration extends AbstractMigration
{
    /**
     * Spaltenname => DDL.
     */
    private const COLUMNS = [
        'iceplan_folder' => 'ADD iceplan_folder BINARY(16) DEFAULT NULL',
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_module'])) {
            return false;
        }

        return [] !== $this->getMissingColumns();
    }

    public function run(): MigrationResult
    {
        $missing = $this->getMissingColumns();

        if ([] === $missing) {
            return $this->createResult(true, 'No columns were missing in tl_module.');
        }

        $this->connection->executeStatement(
            'ALTER TABLE tl_module '.implode(', ', array_values($missing))
        );

        return $this->createResult(
            true,
            'Added ' . implode(', ', array_keys($missing)) . ' to tl_module.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMissingColumns(): array
    {
        // listTableColumns() liefert die Spalten in Kleinschreibung
        $existing = $this->connection->createSchemaManager()->listTableColumns('tl_module');

        return array_filter(
            self::COLUMNS,
            static fn (string $column): bool => !isset($existing[strtolower($column)]),
            ARRAY_FILTER_USE_KEY
        );
    }
}
