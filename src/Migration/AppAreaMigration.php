<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Legt die Tabellen des Backend-Bereichs "App" an (tl_app_screen, tl_app_block, tl_app_flag).
 *
 * Die Spalten entsprechen den DCA-Dateien contao/dca/tl_app_*.php. Der Schema-Abgleich ist auf diesem
 * Datenbestand nicht benutzbar (er würde Alt-Spalten entfernter Extensions löschen, siehe AGENTS.md),
 * deshalb legt diese Migration die Tabellen an: "contao:migrate --migrations-only".
 */
class AppAreaMigration extends AbstractMigration
{
    /** Sichtbarkeitsspalten, die alle drei Tabellen teilen. */
    private const VISIBILITY = "
        published TINYINT(1) DEFAULT 0 NOT NULL,
        start VARCHAR(10) DEFAULT '' NOT NULL,
        stop VARCHAR(10) DEFAULT '' NOT NULL,
        minAppVersion VARCHAR(20) DEFAULT '' NOT NULL,
        platform VARCHAR(16) DEFAULT '' NOT NULL";

    private const OPTIONS = 'ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        return [] !== $this->missingTables();
    }

    public function run(): MigrationResult
    {
        $missing = $this->missingTables();

        foreach ($missing as $sql) {
            $this->connection->executeStatement($sql);
        }

        return $this->createResult(true, 'Created ' . implode(', ', array_keys($missing)) . '.');
    }

    /**
     * @return array<string, string> Tabelle => CREATE TABLE
     */
    private function missingTables(): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $missing = [];

        foreach ($this->definitions() as $table => $sql) {
            if (!$schemaManager->tablesExist([$table])) {
                $missing[$table] = $sql;
            }
        }

        return $missing;
    }

    /**
     * @return array<string, string>
     */
    private function definitions(): array
    {
        $visibility = self::VISIBILITY;
        $options = self::OPTIONS;

        return [
            'tl_app_screen' => "CREATE TABLE tl_app_screen (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                tstamp INT UNSIGNED DEFAULT 0 NOT NULL,
                sortOrder INT UNSIGNED DEFAULT 0 NOT NULL,
                screenKey VARCHAR(64) DEFAULT '' NOT NULL,
                title VARCHAR(255) DEFAULT '' NOT NULL,
                icon VARCHAR(64) DEFAULT '' NOT NULL,
                placement VARCHAR(16) DEFAULT 'tab' NOT NULL,
                kind VARCHAR(16) DEFAULT 'screen' NOT NULL,
                url VARCHAR(2048) DEFAULT '' NOT NULL,
                isHome TINYINT(1) DEFAULT 0 NOT NULL,
                $visibility,
                INDEX screenKey (screenKey),
                PRIMARY KEY (id)
            ) $options",

            'tl_app_block' => "CREATE TABLE tl_app_block (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                pid INT UNSIGNED DEFAULT 0 NOT NULL,
                sorting INT UNSIGNED DEFAULT 0 NOT NULL,
                tstamp INT UNSIGNED DEFAULT 0 NOT NULL,
                blockType VARCHAR(32) DEFAULT '' NOT NULL,
                blockTitle VARCHAR(255) DEFAULT '' NOT NULL,
                blockText MEDIUMTEXT DEFAULT NULL,
                blockPlain TEXT DEFAULT NULL,
                blockLevel VARCHAR(2) DEFAULT 'h2' NOT NULL,
                blockImage BINARY(16) DEFAULT NULL,
                blockFile BINARY(16) DEFAULT NULL,
                blockAlt VARCHAR(255) DEFAULT '' NOT NULL,
                blockCaption VARCHAR(255) DEFAULT '' NOT NULL,
                blockUrl VARCHAR(2048) DEFAULT '' NOT NULL,
                blockLabel VARCHAR(255) DEFAULT '' NOT NULL,
                blockItems TEXT DEFAULT NULL,
                blockOrdered TINYINT(1) DEFAULT 0 NOT NULL,
                blockProvider VARCHAR(16) DEFAULT 'youtube' NOT NULL,
                blockVideoId VARCHAR(64) DEFAULT '' NOT NULL,
                blockLimit SMALLINT UNSIGNED DEFAULT 0 NOT NULL,
                blockCategory VARCHAR(64) DEFAULT '' NOT NULL,
                blockPartnerCategory VARCHAR(32) DEFAULT '' NOT NULL,
                blockLayout VARCHAR(16) DEFAULT '' NOT NULL,
                blockStatus VARCHAR(16) DEFAULT 'upcoming' NOT NULL,
                blockCompact TINYINT(1) DEFAULT 0 NOT NULL,
                blockPosition VARCHAR(16) DEFAULT '' NOT NULL,
                blockArticle INT UNSIGNED DEFAULT 0 NOT NULL,
                blockContent INT UNSIGNED DEFAULT 0 NOT NULL,
                $visibility,
                INDEX pid (pid),
                PRIMARY KEY (id)
            ) $options",

            'tl_app_flag' => "CREATE TABLE tl_app_flag (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                tstamp INT UNSIGNED DEFAULT 0 NOT NULL,
                flagKey VARCHAR(64) DEFAULT '' NOT NULL,
                flagValue VARCHAR(255) DEFAULT '' NOT NULL,
                description VARCHAR(255) DEFAULT '' NOT NULL,
                $visibility,
                INDEX flagKey (flagKey),
                PRIMARY KEY (id)
            ) $options",
        ];
    }
}
