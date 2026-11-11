<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalten des Schrittassistenten an (Meldungen, Schritt-Einleitung, Summenregel).
 *
 * Die Spalten stehen in contao/dca/tl_form_field.php. Der Schema-Abgleich ist auf diesem
 * Datenbestand nicht benutzbar (siehe AGENTS.md), daher als Migration:
 * "contao:migrate --migrations-only".
 */
class FormStepsMigration extends AbstractMigration
{
    private const COLUMNS = [
        'sfStepLead' => "ADD sfStepLead VARCHAR(255) DEFAULT '' NOT NULL",
        'sfMsgRequired' => "ADD sfMsgRequired VARCHAR(255) DEFAULT '' NOT NULL",
        'sfMsgInvalid' => "ADD sfMsgInvalid VARCHAR(255) DEFAULT '' NOT NULL",
        'sfSumGroup' => "ADD sfSumGroup VARCHAR(64) DEFAULT '' NOT NULL",
        'sfSumMin' => 'ADD sfSumMin INT UNSIGNED DEFAULT 0 NOT NULL',
        'sfSumUnit' => "ADD sfSumUnit VARCHAR(32) DEFAULT '' NOT NULL",
        'sfSumMessage' => "ADD sfSumMessage VARCHAR(255) DEFAULT '' NOT NULL",
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        if (!$this->connection->createSchemaManager()->tablesExist(['tl_form_field'])) {
            return false;
        }

        return [] !== $this->getMissingColumns();
    }

    public function run(): MigrationResult
    {
        $missing = $this->getMissingColumns();

        if ([] === $missing) {
            return $this->createResult(true, 'No columns were missing in tl_form_field.');
        }

        $this->connection->executeStatement('ALTER TABLE tl_form_field '.implode(', ', array_values($missing)));

        return $this->createResult(true, 'Added '.implode(', ', array_keys($missing)).' to tl_form_field.');
    }

    /**
     * @return array<string, string>
     */
    private function getMissingColumns(): array
    {
        $existing = $this->connection->createSchemaManager()->listTableColumns('tl_form_field');

        return array_filter(
            self::COLUMNS,
            static fn (string $column): bool => !isset($existing[strtolower($column)]),
            ARRAY_FILTER_USE_KEY
        );
    }
}
