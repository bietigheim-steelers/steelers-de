<?php
// src/EventListener/GeneratePageListener.php
namespace App\Tilastot\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageRegular;
use Contao\LayoutModel;
use Contao\PageModel;
use Mixpanel;

#[AsHook('generatePage')]
class GeneratePageListener
{
  private string $mixpanelProjectToken;

  public function __construct(string $mixpanel_project_token)
  {
    $this->mixpanelProjectToken = $mixpanel_project_token;
  }

  public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
  {

    $mp = Mixpanel::getInstance($this->mixpanelProjectToken);

    $mp->identify($this->getUserId());
    $mp->track("access", [
      "path" => $_SERVER['REQUEST_URI'],
      "referer" => $_SERVER['HTTP_REFERER'],
      "agent" => $_SERVER['HTTP_USER_AGENT'],
    ]);
  }

  private function getUserId()
  {
    $data = $_SERVER['REMOTE_ADDR'] . '-' . $_SERVER['HTTP_USER_AGENT'];
    return hash('sha256', $data);
  }
}
