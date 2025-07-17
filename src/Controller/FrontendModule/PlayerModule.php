<?php

namespace App\Tilastot\Module;

use App\Tilastot\Model\Players;
use App\Tilastot\Model\PlayerStats;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\Template;
use Contao\StringUtil;
use Contao\ArrayUtil;
use Contao\FilesModel;
use Contao\Input;
use Contao\Environment;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PlayerModule extends AbstractFrontendModuleController
{

  protected $strTemplate = 'mod_player';
  protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
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
    $template->headlineUnit = $this->hl;
    $template->cssId = $this->cssID[0];
    $template->cssClass = $this->cssID[1];
    return $template->getResponse();
  }

  public function generate()
  {
    if (TL_MODE == 'BE') {
      /** @var \BackendTemplate|object $objTemplate */
      $objTemplate = new \BackendTemplate('be_wildcard');
      $objTemplate->wildcard = '### HOLEMA SPIELERDETAILS ###';
      $objTemplate->title = $this->headline;
      $objTemplate->id = $this->id;
      $objTemplate->link = $this->name;
      $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;
      return $objTemplate->parse();
    }
    Input::get('auto_item');
  }
}
