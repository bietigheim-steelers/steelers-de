<?php

declare(strict_types=1);

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt tl_page.forwardQuery an (siehe contao/dca/tl_page.php).
 *
 * Als Migration, weil der Schema-Abgleich auf diesem Datenbestand nicht benutzbar ist
 * (siehe AGENTS.md).
 */
class PageForwardQueryMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        return $schemaManager->tablesExist(['tl_page'])
            && !isset($schemaManager->listTableColumns('tl_page')['forwardquery']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("ALTER TABLE tl_page ADD forwardQuery TINYINT(1) DEFAULT 0 NOT NULL");

        return $this->createResult(true, 'Added forwardQuery to tl_page.');
    }
}
