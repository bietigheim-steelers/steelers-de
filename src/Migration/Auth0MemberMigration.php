<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Spalten fuer den Auth0-Frontend-Login an.
 *
 * - tl_member.auth0Sub: Auth0-Benutzerkennung ("sub"), Schluessel der Zuordnung Login -> Mitglied
 * - tl_member_group.auth0Role: Auth0-Rolle, die dieser Gruppe zugeordnet ist
 *
 * Als Migration ausrollbar mit "contao:migrate --migrations-only" (der Schema-Abgleich ist auf
 * diesem Datenbestand nicht benutzbar, siehe AGENTS.md).
 */
class Auth0MemberMigration extends AbstractMigration
{
    /**
     * Tabelle => Spaltenname => DDL.
     */
    private const COLUMNS = [
        'tl_member' => [
            'auth0Sub' => "ADD auth0Sub VARCHAR(255) DEFAULT '' NOT NULL, ADD INDEX auth0Sub (auth0Sub)",
        ],
        'tl_member_group' => [
            'auth0Role' => "ADD auth0Role VARCHAR(64) DEFAULT '' NOT NULL",
        ],
    ];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return [] !== $this->getMissing();
    }

    public function run(): MigrationResult
    {
        $done = [];

        foreach ($this->getMissing() as $table => $columns) {
            $this->connection->executeStatement('ALTER TABLE '.$table.' '.implode(', ', array_values($columns)));
            $done[] = $table.'.'.implode(', '.$table.'.', array_keys($columns));
        }

        return $this->createResult(true, [] === $done ? 'Nothing to do.' : 'Added '.implode(', ', $done).'.');
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function getMissing(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $missing = [];

        foreach (self::COLUMNS as $table => $columns) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            // listTableColumns() liefert die Spalten in Kleinschreibung
            $existing = $schemaManager->listTableColumns($table);
            $absent = array_filter(
                $columns,
                static fn (string $column): bool => !isset($existing[strtolower($column)]),
                ARRAY_FILTER_USE_KEY,
            );

            if ([] !== $absent) {
                $missing[$table] = $absent;
            }
        }

        return $missing;
    }
}
