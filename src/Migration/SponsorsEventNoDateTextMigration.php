<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalte für den frei konfigurierbaren Text an, der bei Sponsor-Events ohne
 * Datum statt des Datums angezeigt wird. Als Migration statt Schema-Abgleich, siehe
 * SponsorsEventTextsMigration.
 */
class SponsorsEventNoDateTextMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        return $schemaManager->tablesExist(['tl_sponsors_event'])
            && !isset($schemaManager->listTableColumns('tl_sponsors_event')['nodatetext']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement(
            "ALTER TABLE tl_sponsors_event ADD noDateText VARCHAR(255) DEFAULT '' NOT NULL"
        );

        return $this->createResult(true, 'Added noDateText to tl_sponsors_event.');
    }
}
