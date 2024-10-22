<?php

namespace App\Tilastot\Page;

use Contao\CoreBundle\ServiceAnnotation\Page;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\Input;
use Contao\Environment;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;

use App\Tilastot\Model\Players;

/**
 * @Page(contentComposition=true)
 */
class PlayerPageController
{
  public function __invoke(PageModel $pageModel): Response
  {
    // The legacy framework relies on the global $objPage variable
    global $objPage;
    $objPage = $pageModel;

    $player = Players::findByAlias(Input::get('auto_item'));
    if (!$player) {
      throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
    }

    $pageModel->pageTitle  = strip_tags(\StringUtil::stripInsertTags($player->firstname) . ' ' . \StringUtil::stripInsertTags($player->lastname));

    // Render the page using the PageRegular handler from the legacy framework
    return (new PageRegular())->getResponse($pageModel, true);
  }
}