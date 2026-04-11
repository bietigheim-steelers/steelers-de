<?php
// src/EventListener/GeneratePageListener.php
namespace App\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageRegular;
use Contao\LayoutModel;
use Contao\PageModel;
use Mixpanel;

#[AsHook('generatePage')]
class GeneratePageListener
{
  private string $mixpanelProjectToken;
  private array $blacklist = ['assets', 'apple-touch-icon.png', 'apple-touch-icon-120x120.png'];

  public function __construct(string $mixpanel_project_token)
  {
    $this->mixpanelProjectToken = $mixpanel_project_token;
  }

  public function __invoke(PageModel $pageModel, LayoutModel $layout, PageRegular $pageRegular): void
  {

    $mp = Mixpanel::getInstance($this->mixpanelProjectToken);
    $mp->identify($this->getUserId());

    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $queryParams = [];
    parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $queryParams);
    $isFacebookReferer = isset($queryParams['fbclid']);

    $pathElements = explode('/', trim($requestUri, '/'));
    if (in_array($pathElements[0], $this->blacklist)) {
      return;
    }

    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (stripos($userAgent, 'bot') !== false) {
      return;
    }

    $mp->track($requestUri, [
      "referer" => $_SERVER['HTTP_REFERER'] ?? '',
      "agent" => $userAgent,
      "facebook_referer" => $isFacebookReferer,
    ]);
  }

  private function getUserId()
  {
    $data = $_SERVER['REMOTE_ADDR'] . '-' . $_SERVER['HTTP_USER_AGENT'];
    return hash('sha256', $data);
  }
}
