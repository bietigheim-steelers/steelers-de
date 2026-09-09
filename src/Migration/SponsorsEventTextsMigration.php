<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die eventspezifischen Texte an, mit denen sich mehrere Sponsor-Events ein
 * Formular und eine Notification-Center-Nachricht teilen können.
 *
 * Die Spalten stehen in contao/dca/tl_sponsors_event.php und würden auch vom
 * Schema-Abgleich erzeugt – der ist auf diesem Datenbestand aber nicht benutzbar, weil
 * er zahlreiche Alt-Spalten entfernter Extensions löschen würde (siehe AGENTS.md).
 * Als Migration lässt sich die Änderung mit "contao:migrate --migrations-only"
 * ausrollen.
 */
class SponsorsEventTextsMigration extends AbstractMigration
{
    /**
     * Spaltenname => DDL.
     */
    private const COLUMNS = [
        'confirmationText' => 'ADD confirmationText TEXT DEFAULT NULL',
        'notificationSubject' => "ADD notificationSubject VARCHAR(255) DEFAULT '' NOT NULL",
        'notificationText' => 'ADD notificationText TEXT DEFAULT NULL',
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
