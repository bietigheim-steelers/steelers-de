<?php

namespace App\EventListener;

use Contao\Module;
use Contao\Date;
use Contao\Input;
use Contao\Config;
use Contao\NewsModel;


class NewsListFetchItemsListener
{
  public function __invoke(array $newsArchives, ?bool $featuredOnly, int $limit, int $offset, Module $module)
  {

    // only use this hook for the videportal module (id 146)
    if ($module->id == '146') {
      $t = NewsModel::getTable();

      // Set the item from the auto_item parameter
      if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem')) {
        Input::setGet('items', Input::get('auto_item'));
      }

      $displayedNews = NewsModel::findBy('alias', Input::get('items'), ['limit' => 1]);

      if ($displayedNews->game_id == '0') {
        // return empty collection if no game is set
        return NewsModel::findBy(array("$t.pid='nuller'"), null);
      }

      $time = Date::floorToMinute();
      $arrOptions['limit']  = 6;
      $arrOptions['offset'] = 0;

      return NewsModel::findBy(
        array(
          "$t.pid IN(" . implode(',', array_map('\intval', $newsArchives)) . ")",
          "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)",
          "$t.game_id = $displayedNews->game_id"
        ),
        null,
        $arrOptions
      );
    }

    return false;
  }
}
