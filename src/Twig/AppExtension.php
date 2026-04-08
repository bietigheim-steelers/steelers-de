<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\HttpFoundation\RequestStack;
use Contao\ArticleModel;
use Contao\PageModel;

class AppExtension extends AbstractExtension
{
  private $requestStack;

  public function __construct(RequestStack $requestStack)
  {
    $this->requestStack = $requestStack;
  }

  public function getFilters(): array
  {
    return [
      // Register a new filter named "page_title" and tell Twig which method to execute
      new TwigFilter('page_title', [$this, 'pageTitle']),
      new TwigFilter('truncate_text', [$this, 'truncateText']),
      new TwigFilter('get_game_details', [$this, 'getGameDetails']),
      new TwigFilter('add_domain', [$this, 'addDomain']),
      new TwigFilter('get_youtube_thumbnail', [$this, 'getYoutubeThumbnail']),
      new TwigFilter('add_root', [$this, 'addRoot']),
    ];
  }

  // The first argument ($value) is the string, the filter is applied on
  public function pageTitle(string $value, int $articleId): string
  {

    if ($articleId) {
      $article = ArticleModel::findByPk($articleId);
      $parentPage = PageModel::findByPk($article->pid);
      $parentParentPage = PageModel::findByPk($parentPage->pid);
      if ($parentParentPage && $parentParentPage->alias) {
        return $parentParentPage->alias;
      }
    }

    return $value;
  }

  public function truncateText(string $value, int $targetLength): string
  {
    if (strlen($value) > $targetLength) {
      $parts = preg_split('/([\s\n\r]+)/', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
      $parts_count = count($parts);

      $length = 0;
      $last_part = 0;
      for (; $last_part < $parts_count; ++$last_part) {
        $length += strlen($parts[$last_part]);
        if ($length > $targetLength) {
          break;
        }
      }

      return trim(implode(array_slice($parts, 0, $last_part)));
    }
    return $value;
  }

  public function getGameDetails(int $gameId): Object|null
  {
    $game = \App\Model\Games::findByPk($gameId);

    if ($game) {
      $game->homeTeamDetails = \App\Model\Standings::findByIdAndRound($game->hometeam, $game->round);
      $game->awayTeamDetails = \App\Model\Standings::findByIdAndRound($game->awayteam, $game->round);
      if ($game->homeTeamDetails && isset($game->homeTeamDetails['logo']) && strpos($game->homeTeamDetails['logo'], '/') === 0) {
        $game->homeTeamLogo = __DIR__ . '/../..' . $game->homeTeamDetails['logo'];
      }
      if ($game->awayTeamDetails && isset($game->awayTeamDetails['logo']) && strpos($game->awayTeamDetails['logo'], '/') === 0) {
        $game->awayTeamLogo = __DIR__ . '/../..' . $game->awayTeamDetails['logo'];
      }

      return $game;
    }

    return null;
  }

  public function addDomain(string $url): string
  {
    $request = $this->requestStack->getCurrentRequest();
    if ($request) {
      $schemeAndHost = $request->getSchemeAndHttpHost();
      return $schemeAndHost . $url;
    }
    return $url;
  }

  public function getYoutubeThumbnail(string $video_url): string
  {
    parse_str(parse_url($video_url, PHP_URL_QUERY), $queryParams);
    $video_id = $queryParams['v'];
    $url = 'https://img.youtube.com/vi/' . $video_id . '/hq2.jpg';
    $tmpDir = $this->addRoot('/var/tmp');
    $localFile = $tmpDir . DIRECTORY_SEPARATOR . $video_id . '_hq2.jpg';

    if (!file_exists($localFile)) {
      $ch = curl_init($url);
      $fp = fopen($localFile, 'wb');

      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

      curl_exec($ch);
      curl_close($ch);
      fclose($fp);
    }

    return $localFile;
  }

  public function addRoot(string $path): string
  {
    return __DIR__ . '/../..' . $path;
  }
}
