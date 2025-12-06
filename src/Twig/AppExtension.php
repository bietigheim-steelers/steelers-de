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
}
