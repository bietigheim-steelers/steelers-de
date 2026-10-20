<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die CleverReach-Flow-URL am Formular an.
 *
 * Die Spalte steht in contao/dca/tl_form.php und wuerde auch vom Schema-Abgleich
 * erzeugt – der ist auf diesem Datenbestand aber nicht benutzbar, weil er zahlreiche
 * Alt-Spalten entfernter Extensions loeschen wuerde (siehe AGENTS.md). Als Migration
 * laesst sich die Aenderung mit "contao:migrate --migrations-only" ausrollen.
 */
class CleverReachFormMigration extends AbstractMigration
{
    /**
     * Spaltenname => DDL.
     */
    private const COLUMNS = [
        'cleverreachUrl' => "ADD cleverreachUrl VARCHAR(255) DEFAULT '' NOT NULL",
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_form'])) {
            return false;
        }

        return [] !== $this->getMissingColumns();
    }

    public function run(): MigrationResult
    {
        $missing = $this->getMissingColumns();

        if ([] === $missing) {
            return $this->createResult(true, 'No columns were missing in tl_form.');
        }

        $this->connection->executeStatement(
            'ALTER TABLE tl_form '.implode(', ', array_values($missing))
        );

        return $this->createResult(
            true,
            'Added ' . implode(', ', array_keys($missing)) . ' to tl_form.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function getMissingColumns(): array
    {
        // listTableColumns() liefert die Spalten in Kleinschreibung
        $existing = $this->connection->createSchemaManager()->listTableColumns('tl_form');

        return array_filter(
            self::COLUMNS,
            static fn (string $column): bool => !isset($existing[strtolower($column)]),
            ARRAY_FILTER_USE_KEY
        );
    }
}
