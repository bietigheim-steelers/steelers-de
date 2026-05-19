<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class CreateEventRegistrationMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function getName(): string
    {
        return 'Create tl_event_registration table and add registration fields to tl_calendar_events';
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        return !$schemaManager->tablesExist(['tl_event_registration']);
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `tl_event_registration` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `pid` int(10) unsigned NOT NULL DEFAULT 0,
                `tstamp` int(10) unsigned NOT NULL DEFAULT 0,
                `dateAdded` int(10) unsigned NOT NULL DEFAULT 0,
                `submitterName` varchar(255) NOT NULL DEFAULT \'\',
                `submitterEmail` varchar(255) NOT NULL DEFAULT \'\',
                `submitterPhone` varchar(64) NOT NULL DEFAULT \'\',
                `data` mediumtext NULL,
                `ip` varchar(64) NOT NULL DEFAULT \'\',
                PRIMARY KEY (`id`),
                KEY `pid` (`pid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $columns = $this->connection->createSchemaManager()->listTableColumns('tl_calendar_events');
        $existingColumns = array_map('strtolower', array_keys($columns));

        if (!in_array('regenabled', $existingColumns, true)) {
            $this->connection->executeStatement(
                'ALTER TABLE `tl_calendar_events` ADD `regEnabled` tinyint(1) unsigned NOT NULL DEFAULT 0'
            );
        }

        if (!in_array('regformtype', $existingColumns, true)) {
            $this->connection->executeStatement(
                "ALTER TABLE `tl_calendar_events` ADD `regFormType` varchar(16) NOT NULL DEFAULT 'custom'"
            );
        }

        if (!in_array('regformid', $existingColumns, true)) {
            $this->connection->executeStatement(
                'ALTER TABLE `tl_calendar_events` ADD `regFormId` int(10) unsigned NOT NULL DEFAULT 0'
            );
        }

        if (!in_array('regcustomfields', $existingColumns, true)) {
            $this->connection->executeStatement(
                'ALTER TABLE `tl_calendar_events` ADD `regCustomFields` blob NULL'
            );
        }

        if (!in_array('regtoken', $existingColumns, true)) {
            $this->connection->executeStatement(
                "ALTER TABLE `tl_calendar_events` ADD `regToken` varchar(64) NOT NULL DEFAULT ''"
            );
        }

        if (!in_array('regdeadline', $existingColumns, true)) {
            $this->connection->executeStatement(
                'ALTER TABLE `tl_calendar_events` ADD `regDeadline` bigint(20) NULL'
            );
        }

        if (!in_array('regmaxparticipants', $existingColumns, true)) {
            $this->connection->executeStatement(
                'ALTER TABLE `tl_calendar_events` ADD `regMaxParticipants` int(10) unsigned NULL'
            );
        }

        if (!in_array('regnotificationemail', $existingColumns, true)) {
            $this->connection->executeStatement(
                "ALTER TABLE `tl_calendar_events` ADD `regNotificationEmail` varchar(255) NOT NULL DEFAULT ''"
            );
        }

        return $this->createResult(true);
    }
}
