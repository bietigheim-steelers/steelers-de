<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
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
      new TwigFilter('get_url_params', [$this, 'getUrlParams']),
      new TwigFilter('decode_entities', [$this, 'decodeEntities']),
      new TwigFilter('parse_int', [$this, 'parseInt']),

    ];
  }

  public function getFunctions(): array
  {
    return [
      new TwigFunction('next_home_game', [$this, 'getNextHomeGame']),
      new TwigFunction('ticket_banner_images', [$this, 'getTicketBannerImages']),
      new TwigFunction('ticket_banner_cutouts', [$this, 'getTicketBannerCutouts']),
    ];
  }

  // The first argument ($value) is the string, the filter is applied on
  // Replicates the legacy insert-tag logic of ce_text_page_title.html5:
  //   {{article_title::{{page::alias}}}}        -> article whose alias matches the current page alias
  //   {{article_title::{{page::parentAlias}}}}  -> article whose alias matches the parent page alias
  //   fallback                                  -> $value (titleText)
  public function pageTitle(string $value, int $articleId): string
  {
    $article = ArticleModel::findByPk($articleId);

    if (!$article) {
      return $value;
    }

    $page = PageModel::findByPk($article->pid);
    $title = '';

    // 1. Article whose alias matches the current page's alias
    if ($page && $page->alias) {
      $aliasArticle = ArticleModel::findByIdOrAlias($page->alias);
      if ($aliasArticle) {
        $title = $aliasArticle->title;
      }
    }

    // 2. Fallback: article whose alias matches the parent page's alias
    if (!$title && $page) {
      $parentPage = PageModel::findByPk($page->pid);
      if ($parentPage && $parentPage->alias) {
        $aliasArticle = ArticleModel::findByIdOrAlias($parentPage->alias);
        if ($aliasArticle) {
          $title = $aliasArticle->title;
        }
      }
    }

    // 3. Final fallback: the content element's titleText
    if (!$title) {
      $title = $value;
    }

    return $title;
  }

  public function truncateText(string $value, int $targetLength): string
  {
    $strippedValue = trim(strip_tags($value));

    if (mb_strlen($strippedValue) > $targetLength) {
      $parts = preg_split('/([\s\n\r]+)/', $strippedValue, -1, PREG_SPLIT_DELIM_CAPTURE);
      $parts_count = count($parts);

      $length = 0;
      $last_part = 0;
      for (; $last_part < $parts_count; ++$last_part) {
        $length += mb_strlen($parts[$last_part]);
        if ($length > $targetLength) {
          break;
        }
      }

      return trim(implode(array_slice($parts, 0, $last_part))) . "…";
    }
    return $strippedValue;
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

  public function addDomain(?string $url): ?string
  {
    if ($url === null) {
      return null;
    }
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
    $url = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
    $tmpDir = $this->addRoot('/var/tmp');
    $localFile = $tmpDir . DIRECTORY_SEPARATOR . $video_id . '_hqdefault.jpg';

    if (!file_exists($localFile)) {
      $ch = curl_init($url);
      $fp = fopen($localFile, 'wb');

      curl_setopt($ch, CURLOPT_FILE, $fp);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

      curl_exec($ch);
      fclose($fp);
    }

    return $localFile;
  }

  public function getUrlParams(string $url): array
  {
    parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
    return $queryParams;
  }

  public function addRoot(string $path): string
  {
    return __DIR__ . '/../..' . $path;
  }

  public function decodeEntities(string $value): string
  {
    // Decode repeatedly to also handle values that are encoded more than once.
    $decoded = $value;

    for ($i = 0; $i < 3; ++$i) {
      $next = html_entity_decode($decoded, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

      if ($next === $decoded) {
        break;
      }

      $decoded = $next;
    }

    return $decoded;
  }
  public function parseInt(string $value): int
  {
    return (int)$value;
  }

  private const MY_TEAM_ID = 36;
  private const GAME_DURATION = 3 * 3600;
  private const WEEKDAYS = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  private const MONTHS = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

  /**
   * Next home game of the Steelers that has not finished yet, prepared for display.
   */
  public function getNextHomeGame(): ?array
  {
    $games = \App\Model\Games::findAll([
      'order'  => ' gamedate ASC',
      'limit'  => ' LIMIT 10',
      'column' => ['gamedate >= ? AND hometeam = ? AND optional != ?'],
      'value'  => [strtotime('today'), self::MY_TEAM_ID, true],
    ]);

    if (!$games) {
      return null;
    }

    $now = time();

    foreach ($games as $game) {
      [$hour, $minute] = array_pad(array_map('intval', explode(':', (string) $game->gametime)), 2, 0);
      $start = (new \DateTimeImmutable('@' . $game->gamedate))
        ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
        ->setTime($hour, $minute);

      // keep today's game visible until it should be over
      if ($start->getTimestamp() + self::GAME_DURATION < $now) {
        continue;
      }

      $home = \App\Model\Standings::findByIdAndRound($game->hometeam, $game->round);
      $away = \App\Model\Standings::findByIdAndRound($game->awayteam, $game->round);
      $logo = $away['logo'] ?? null;
      $homeLogo = $home['logo'] ?? null;
      $days = (int) (new \DateTimeImmutable('today'))->diff($start->setTime(0, 0))->format('%r%a');

      return [
        'weekday'  => self::WEEKDAYS[(int) $start->format('w')],
        'day'      => $start->format('j') . '.',
        'month'    => self::MONTHS[(int) $start->format('n')],
        'time'     => $start->format('H:i'),
        'datetime' => $start->format('c'),
        'opponent' => $away['name'] ?? '',
        'home'     => $home['name'] ?? '',
        'homeLogo' => (is_string($homeLogo) && str_starts_with($homeLogo, '/')) ? $homeLogo : null,
        'logo'     => (is_string($logo) && str_starts_with($logo, '/')) ? $logo : null,
        'when'     => match (true) {
          $days <= 0 => 'Heute',
          $days === 1 => 'Morgen',
          default => 'In ' . $days . ' Tagen',
        },
      ];
    }

    return null;
  }

  /**
   * Pool of background images for the ticket banner: every image in files/steelers/ticketbanner.
   * Falls back to the arena picture while the folder is empty.
   */
  public function getTicketBannerImages(): array
  {
    $files = glob($this->addRoot('/files/steelers/ticketbanner/*.{jpg,jpeg,png,webp,avif}'), GLOB_BRACE) ?: [];
    sort($files);

    if (!$files) {
      return ['/files/steelers/layout/arena-bg.jpg'];
    }

    return array_map(static fn (string $file): string => '/files/steelers/ticketbanner/' . rawurlencode(basename($file)), $files);
  }

  /**
   * Optional player cutouts (transparent png/webp) that break out of the ticket banner.
   * Lives in files/steelers/ticketbanner/freisteller; empty while the folder is missing.
   */
  public function getTicketBannerCutouts(): array
  {
    $files = glob($this->addRoot('/files/steelers/ticketbanner/freisteller/*.{png,webp,avif}'), GLOB_BRACE) ?: [];
    sort($files);

    return array_map(static fn (string $file): string => '/files/steelers/ticketbanner/freisteller/' . rawurlencode(basename($file)), $files);
  }
}
