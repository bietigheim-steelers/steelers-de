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
    'ce_text_default_left' => 'content_element/text/default_left',
    'ce_template_player' => 'content_element/template/player',
    'wrapperStart' => 'content_element/wrapper_block_start_element',
    'wrapperStop' => 'content_element/wrapper_block_end_element',
    'ce_template_people' => 'content_element/template/people',
    'ce_wrapperStart_grid' => 'content_element/wrapper_block_start_grid',
    'ce_gallery_scroller' => 'content_element/gallery/scroller',
    'ce_hyperlink_card' => 'content_element/hyperlink/card',
    'ce_hyperlink_button' => 'content_element/hyperlink/button',
    'ce_hyperlink_content' => 'content_element/hyperlink/content',
    'ce_player_home' => 'content_element/player/home',
    'ce_player_hospitality' => 'content_element/player/hospitality',
    'ce_image_centered' => 'content_element/image/centered',
    'ce_headline_page_title' => 'content_element/headline/page_title',
    'ce_text_one_column' => 'content_element/text/one_column',
    'ce_text_highlight' => 'content_element/text/highlight',
    'ce_text_tickets_progress' => 'content_element/template/progress',
    'ce_text_tickets_progress2' => 'content_element/template/progress',
    'ce_template_progress_crowdfunding' => 'content_element/template/progress',

    //'ce_table_icon_list' => 'content_element/',
    //'ce_table_pricing' => 'content_element/',
    //'ce_table_roster' => 'content_element/',
    //'ce_template_error' => 'content_element/',
    //'ce_template_form_error' => 'content_element/',
    //'ce_template_shop_home' => 'content_element/',
    //'ce_text_businessschmiede' => 'content_element/',
    //'ce_text_businessschmiede_header' => 'content_element/',
    //'ce_text_businessschmiede_pricing' => 'content_element/',
    //'ce_text_popup' => 'content_element/',
  ];
  private const MODULE_MAPPING = [
    'mod_partners' => 'frontend_module/partners_module/card',
    'mod_partners_blocktext' => 'frontend_module/partners_module/blocktext',
    'mod_standings_home' => 'frontend_module/standings_module/home',
    'mod_standings' => 'frontend_module/standings_module/default',
    'mod_schedule_home_playoffs' => 'frontend_module/schedule_module/playoffs',
    'mod_schedule_list' => 'frontend_module/schedule_module/list',
    'mod_roster' => 'frontend_module/roster_module/default',
    'mod_roster_partner' => 'frontend_module/roster_module/partner',
    'mod_schedule_home' => 'frontend_module/schedule_module/home',
    'mod_partners_frontpage' => 'frontend_module/partners_module/frontpage',

    //'mod_customnav_preheader' => 'frontend_module/',
    //'mod_login_training' => 'frontend_module/',
    //'mod_navigation_default' => 'frontend_module/',
    //'mod_navigation_microsite' => 'frontend_module/',
    //'mod_navigation_mobile' => 'frontend_module/',
    //'mod_newsarchive_list' => 'frontend_module/',
    //'mod_newsarchive_list_videoportal' => 'frontend_module/',
    //'mod_newslist_home_scroller' => 'frontend_module/',
    //'mod_newslist_home_scroller_blue' => 'frontend_module/',
    //'mod_newslist_videoportal' => 'frontend_module/',
    //'mod_newsreader_detail' => 'frontend_module/',
  ];

  private const FORM_MAPPING = [
    //'form_wrapper_newsletter_home' => '',
    //'form_wrapper_season_tickets' => '',
    //'form_wrapper_vip' => '',
  ];
  private const FORM_FIELD_MAPPING = [
    //'form_wrapper_newsletter_home' => '',
    //'form_wrapper_season_tickets' => '',
    //'form_wrapper_vip' => '',
  ];
  private const CONTENT_ELEMENT_MAPPING = [
    'wrapperStart' => 'wrapper_block_start_element',
    'wrapperStop' => 'wrapper_block_end_element',
    //'form_wrapper_season_tickets' => '',
    //'form_wrapper_vip' => '',
  ];

  public function __construct(private readonly Connection $connection) {}


  public function shouldRun(): bool
  {
    $schemaManager = $this->connection->createSchemaManager();

    if (!$schemaManager->tablesExist(['tl_content', 'tl_module', 'tl_form', 'tl_form_field'])) {
      return false;
    }

    $columns = $schemaManager->listTableColumns('tl_content');

    if (!isset($columns['customtpl'])) {
      return false;
    }

    return $this->hasLegacyData('tl_content', 'customTpl', self::CONTENT_MAPPING)
      || $this->hasLegacyData('tl_content', 'type', self::CONTENT_ELEMENT_MAPPING)
      || $this->hasLegacyData('tl_module', 'customTpl', self::MODULE_MAPPING)
      || $this->hasLegacyData('tl_form', 'customTpl', self::FORM_MAPPING)
      || $this->hasLegacyData('tl_form_field', 'customTpl', self::FORM_FIELD_MAPPING);
  }

  private function hasLegacyData(string $table, string $column, array $mapping): bool
  {
    foreach (array_keys($mapping) as $legacyValue) {
      $match = $this->connection->fetchOne(
        "SELECT id FROM $table WHERE $column = ? LIMIT 1",
        [$legacyValue]
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
    foreach (self::CONTENT_ELEMENT_MAPPING as $legacyType => $newType) {
      $updatedRows += $this->connection->update(
        'tl_content',
        ['type' => $newType],
        ['type' => $legacyType]
      );
    }
    foreach (self::MODULE_MAPPING as $legacyTemplate => $newTemplate) {
      $updatedRows += $this->connection->update(
        'tl_module',
        ['customTpl' => $newTemplate],
        ['customTpl' => $legacyTemplate]
      );
    }
    foreach (self::FORM_MAPPING as $legacyTemplate => $newTemplate) {
      $updatedRows += $this->connection->update(
        'tl_form',
        ['customTpl' => $newTemplate],
        ['customTpl' => $legacyTemplate]
      );
    }
    foreach (self::FORM_FIELD_MAPPING as $legacyTemplate => $newTemplate) {
      $updatedRows += $this->connection->update(
        'tl_form_field',
        ['customTpl' => $newTemplate],
        ['customTpl' => $legacyTemplate]
      );
    }

    return $this->createResult(
      true,
      'Updated ' . $updatedRows . ' tl_content, tl_module, and tl_form customTpl values.'
    );
  }
}
