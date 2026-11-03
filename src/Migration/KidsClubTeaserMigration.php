<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalte "kidsBadge" (Text im Button-Stempel des Kids-Club-Teasers) an.
 *
 * Die Spalte steht auch in contao/dca/tl_content.php; als Migration laesst sie sich
 * mit "contao:migrate --migrations-only" ausrollen (siehe AGENTS.md).
 */
class KidsClubTeaserMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        return !isset($schemaManager->listTableColumns('tl_content')['kidsbadge']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement("ALTER TABLE tl_content ADD kidsBadge VARCHAR(32) DEFAULT '' NOT NULL");

        return $this->createResult(true, 'Added kidsBadge to tl_content.');
    }
}
