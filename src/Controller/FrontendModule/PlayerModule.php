<?php

namespace App\Controller\FrontendModule;

use App\Model\Players;
use App\Model\PlayerStats;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Contao\ArrayUtil;
use Contao\FilesModel;
use Contao\Input;
use Contao\Environment;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(category: 'steelers_season_modules')]
class PlayerModule extends AbstractFrontendModuleController
{
  protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
  {
    $player = Players::findOneBy(array('alias = ? AND published = 1'), array(Input::get('auto_item')));
    if (!$player) {
      throw new PageNotFoundException('Page not found: ' . Environment::get('uri'));
    }

    $p = $player->row();
    $pictures = StringUtil::deserialize($p['pictures']);

    if (!empty($pictures) || is_array($pictures)) {
      $files = ArrayUtil::sortByOrderField($pictures, StringUtil::deserialize($p['orderPictures']));
      $allPictures = FilesModel::findMultipleByUuids($files)->fetchAll();
      $p['profilePic'] = $allPictures[0];
      $p['pictures'] = $allPictures;
    }

    $stats = PlayerStats::findAll(array(
      'column'  => array('pid=?'),
      'order'   => 'round DESC',
      'limit'   => '1',
      'value'   => array($p['id'])
    ));

    $template->player = $p;
    if ($stats) {
      $template->stats = $stats->fetchAll()[0];
    }
    $template->cssId = $model->cssID[0];
    $template->cssClass = $model->cssID[1];
    return $template->getResponse(); 
  }

}
