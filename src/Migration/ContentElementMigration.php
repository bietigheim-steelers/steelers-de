<?php

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class ContentElementMigration extends AbstractMigration
{
  private const CONTENT_MAPPING = [
    'ce_text_small' => 'content_element/text/small',
    'ce_text_page_title' => 'content_element/text/page_title',
    'ce_text_default' => 'content_element/text/default',
  ];

  public function __construct(private readonly Connection $connection) {}

  public function shouldRun(): bool
  {
    $schemaManager = $this->connection->createSchemaManager();

    if (!$schemaManager->tablesExist(['tl_content'])) {
      return false;
    }

    $columns = $schemaManager->listTableColumns('tl_content');

    if (!isset($columns['customtpl'])) {
      return false;
    }

    foreach (array_keys(self::CONTENT_MAPPING) as $legacyTemplate) {
      $match = $this->connection->fetchOne(
        'SELECT id FROM tl_content WHERE customTpl = ? LIMIT 1',
        [$legacyTemplate]
      );

      if (false !== $match) {
        return true;
      }
    }

    return false;
  }

  public function run(): MigrationResult
  {

    $updatedRows = 0;

    foreach (self::CONTENT_MAPPING as $legacyTemplate => $newTemplate) {
      $updatedRows += $this->connection->update(
        'tl_content',
        ['customTpl' => $newTemplate],
        ['customTpl' => $legacyTemplate]
      );
    }

    return $this->createResult(
      true,
      'Updated ' . $updatedRows . ' tl_content customTpl values.'
    );
  }
}
