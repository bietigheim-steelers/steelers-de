<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\HttpFoundation\RequestStack;
use Contao\ArticleModel;

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
    ];
  }

  // The first argument ($value) is the string, the filter is applied on
  public function pageTitle(string $value, int $articleId): string
  {

    if ($articleId) {
      $article = ArticleModel::findByPk($articleId);
      if ($article->alias) {
        return $article->alias;
      }
      $parentArticle = ArticleModel::findByPk($article->pid);
      if ($parentArticle && $parentArticle->alias) {
        return $parentArticle->alias;
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
}
