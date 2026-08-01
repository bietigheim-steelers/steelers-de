<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\CoreBundle\Slug\Slug;
use Doctrine\DBAL\Connection;

/**
 * Erzeugt für bestehende Partner einen Alias, damit sie über eine
 * Detailseite erreichbar sind.
 */
class PartnerAliasMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Slug $slug,
    ) {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist(['tl_tilastot_partners'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_tilastot_partners');

        if (!isset($columns['alias'])) {
            return false;
        }

        return false !== $this->connection->fetchOne("SELECT id FROM tl_tilastot_partners WHERE alias = '' LIMIT 1");
    }

    public function run(): MigrationResult
    {
        $rows = $this->connection->fetchAllAssociative(
            "SELECT id, name, displayname FROM tl_tilastot_partners WHERE alias = '' ORDER BY id"
        );

        $taken = array_flip($this->connection->fetchFirstColumn(
            "SELECT alias FROM tl_tilastot_partners WHERE alias != ''"
        ));

        $updated = 0;

        foreach ($rows as $row) {
            // Namenlose Datensätze bekommen einen technischen Alias, damit die
            // Migration nicht bei jedem Durchlauf erneut anspringt.
            $source = trim((string) ($row['displayname'] ?: $row['name'])) ?: 'partner-' . $row['id'];

            $alias = $this->slug->generate(
                (string) $source,
                [],
                static fn (string $candidate): bool => isset($taken[$candidate])
            );

            $taken[$alias] = true;

            $this->connection->update('tl_tilastot_partners', ['alias' => $alias], ['id' => $row['id']]);
            ++$updated;
        }

        return $this->createResult(true, 'Created aliases for ' . $updated . ' partners.');
    }
}
