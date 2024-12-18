<?php
namespace App\Tilastot\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Module;
use Contao\Date;
use Contao\Input;
use Contao\Config;
use Contao\NewsModel;

#[AsHook('newsListFetchItems')]
class NewsListFetchItemsListener
{
public function __invoke(array $newsArchives, ?bool $featuredOnly, int $limit, int $offset, Module $module)
{

  // only use this hook for the videportal module (id 146)
  if ($module->id == '146') {
    
    // Set the item from the auto_item parameter
    if (!isset($_GET['items']) && isset($_GET['auto_item']) && Config::get('useAutoItem')) {
      Input::setGet('items', Input::get('auto_item'));
    }

    var_dump(Input::get('items'));

    $time = Date::floorToMinute();
    $t = NewsModel::getTable();

    //return NewsModel::findBy(
    //    array(
    //      "$t.pid IN(" . implode(',', array_map('\intval', $newsArchives)) . ")",
    //      "$t.published='1' AND ($t.start='' OR $t.start<=$time) AND ($t.stop='' OR $t.stop>$time)"
    //      )
    //);
  }

return false;
}
}