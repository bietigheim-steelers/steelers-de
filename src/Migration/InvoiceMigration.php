<?php

declare(strict_types=1);

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Tabellen für die Rechnungen der Gruppenbestellungen an.
 *
 * Als Migration und nicht über den Schema-Abgleich, weil der auf diesem Datenbestand
 * zahlreiche Alt-Spalten entfernter Extensions löschen würde (siehe AGENTS.md).
 * Ausrollen mit "contao:migrate --migrations-only".
 */
class InvoiceMigration extends AbstractMigration
{
    private const TABLES = [
        'tl_invoice' => <<<'SQL'
            CREATE TABLE tl_invoice (
              id INT UNSIGNED AUTO_INCREMENT NOT NULL,
              tstamp INT UNSIGNED DEFAULT 0 NOT NULL,
              created INT UNSIGNED DEFAULT 0 NOT NULL,
              form_id INT UNSIGNED DEFAULT 0 NOT NULL,
              status VARCHAR(8) DEFAULT 'draft' NOT NULL,
              invoice_number VARCHAR(32) DEFAULT NULL,
              invoice_date INT UNSIGNED DEFAULT 0 NOT NULL,
              recipient TEXT DEFAULT NULL,
              subject VARCHAR(255) DEFAULT '' NOT NULL,
              intro_text TEXT DEFAULT NULL,
              outro_text TEXT DEFAULT NULL,
              vat_rate DECIMAL(5,2) DEFAULT 19.00 NOT NULL,
              items MEDIUMTEXT DEFAULT NULL,
              customer_email VARCHAR(255) DEFAULT '' NOT NULL,
              send_to VARCHAR(255) DEFAULT '' NOT NULL,
              order_data MEDIUMTEXT DEFAULT NULL,
              sent_at INT UNSIGNED DEFAULT 0 NOT NULL,
              sent_to VARCHAR(255) DEFAULT '' NOT NULL,
              sent_by VARCHAR(255) DEFAULT '' NOT NULL,
              sent_count INT UNSIGNED DEFAULT 0 NOT NULL,
              modified_after_sent INT UNSIGNED DEFAULT 0 NOT NULL,
              PRIMARY KEY (id),
              UNIQUE KEY invoice_number (invoice_number),
              KEY tstamp (tstamp)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
        'tl_invoice_counter' => <<<'SQL'
            CREATE TABLE tl_invoice_counter (
              year SMALLINT UNSIGNED NOT NULL,
              last_number INT UNSIGNED DEFAULT 0 NOT NULL,
              PRIMARY KEY (year)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL,
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return [] !== $this->getMissingTables();
    }

    public function run(): MigrationResult
    {
        $missing = $this->getMissingTables();

        foreach ($missing as $sql) {
            $this->connection->executeStatement($sql);
        }

        return $this->createResult(true, 'Created tables '.implode(', ', array_keys($missing)).'.');
    }

    /**
     * @return array<string, string>
     */
    private function getMissingTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();

        return array_filter(
            self::TABLES,
            static fn (string $table): bool => !$schemaManager->tablesExist([$table]),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
