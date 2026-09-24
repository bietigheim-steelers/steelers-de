<?php

namespace App\Utils;

use Contao\Controller;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

/**
 * Creates tl_article / tl_content rows from a JSON payload (see
 * .claude/skills/contao-content-import for the schema and generation rules).
 *
 * Runs inside one DB transaction: either every article/element is created, or
 * nothing is. Field names and the "type" of every content element are
 * validated against the live tl_content/tl_article DCA before anything is
 * written, so a typo is reported instead of silently failing or corrupting
 * the target page.
 */
class ContentImporter
{
    private const RESERVED_ARTICLE_FIELDS = ['id', 'pid', 'sorting', 'tstamp'];

    private const RESERVED_CONTENT_FIELDS = ['id', 'pid', 'ptable', 'sorting', 'tstamp', 'children'];

    /**
     * @return array{articles: list<array{id:int, alias:string, title:string}>}
     */
    public function import(string $json, int $currentUserId): array
    {
        try {
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Ungültiges JSON: ' . $e->getMessage());
        }

        if (!is_array($data)) {
            throw new \RuntimeException('Das JSON muss ein Objekt mit "page_id" und "articles" sein.');
        }

        Controller::loadDataContainer('tl_article');
        Controller::loadDataContainer('tl_content');

        $errors = $this->validate($data);

        if ($errors) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        $pageId = (int) $data['page_id'];
        $db = Database::getInstance();
        $db->query('START TRANSACTION');

        try {
            $created = [];

            foreach (array_values($data['articles']) as $articleData) {
                $created[] = $this->createArticle($pageId, $articleData, $currentUserId);
            }

            $db->query('COMMIT');
        } catch (\Throwable $e) {
            $db->query('ROLLBACK');
            throw $e;
        }

        return ['articles' => $created];
    }

    /**
     * @return list<string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (empty($data['page_id']) || !is_numeric($data['page_id'])) {
            $errors[] = 'page_id fehlt oder ist keine Zahl.';
        } elseif (!PageModel::findById((int) $data['page_id'])) {
            $errors[] = 'page_id ' . $data['page_id'] . ' verweist auf keine existierende Seite.';
        }

        if (empty($data['articles']) || !is_array($data['articles'])) {
            $errors[] = 'articles fehlt oder ist kein Array.';

            return $errors;
        }

        $validTypes = array_diff(
            array_keys($GLOBALS['TL_DCA']['tl_content']['palettes'] ?? []),
            ['__selector__', 'default']
        );

        $articleFields = array_keys($GLOBALS['TL_DCA']['tl_article']['fields'] ?? []);
        $contentFields = array_keys($GLOBALS['TL_DCA']['tl_content']['fields'] ?? []);

        foreach (array_values($data['articles']) as $ai => $article) {
            $path = 'Artikel ' . ($ai + 1);

            if (!is_array($article)) {
                $errors[] = "$path: kein Objekt.";
                continue;
            }

            if (empty($article['title']) || !is_string($article['title'])) {
                $errors[] = "$path: 'title' fehlt oder ist kein String.";
            }

            foreach (array_keys($article) as $field) {
                if ('content' === $field || in_array($field, self::RESERVED_ARTICLE_FIELDS, true)) {
                    continue;
                }

                if (!in_array($field, $articleFields, true)) {
                    $errors[] = "$path: Feld '$field' existiert nicht in tl_article.";
                }
            }

            if (isset($article['content']) && !is_array($article['content'])) {
                $errors[] = "$path: 'content' ist kein Array.";
            }

            foreach (array_values($article['content'] ?? []) as $ci => $content) {
                $errors = [
                    ...$errors,
                    ...$this->validateContent("$path, Element " . ($ci + 1), $content, $validTypes, $contentFields),
                ];
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function validateContent(string $path, mixed $content, array $validTypes, array $contentFields): array
    {
        $errors = [];

        if (!is_array($content)) {
            $errors[] = "$path: kein Objekt.";

            return $errors;
        }

        if (empty($content['type']) || !is_string($content['type'])) {
            $errors[] = "$path: 'type' fehlt oder ist kein String.";
        } elseif (!in_array($content['type'], $validTypes, true)) {
            $errors[] = "$path: unbekannter Inhaltselement-Typ '{$content['type']}'.";
        }

        foreach (array_keys($content) as $field) {
            if (in_array($field, self::RESERVED_CONTENT_FIELDS, true)) {
                continue;
            }

            if (!in_array($field, $contentFields, true)) {
                $errors[] = "$path: Feld '$field' existiert nicht in tl_content.";
            }
        }

        if (isset($content['children'])) {
            if (!is_array($content['children'])) {
                $errors[] = "$path: 'children' ist kein Array.";
            } else {
                foreach (array_values($content['children']) as $ci => $child) {
                    $errors = [
                        ...$errors,
                        ...$this->validateContent("$path, Kind " . ($ci + 1), $child, $validTypes, $contentFields),
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * @return array{id:int, alias:string, title:string}
     */
    private function createArticle(int $pageId, array $articleData, int $currentUserId): array
    {
        $alias = System::getContainer()->get('contao.slug')->generate(
            $articleData['alias'] ?? $articleData['title'],
            $pageId,
            static fn (string $alias): bool => (bool) Database::getInstance()
                ->prepare('SELECT id FROM tl_article WHERE alias = ?')
                ->execute($alias)
                ->numRows
        );

        $row = [
            'pid' => $pageId,
            'sorting' => $this->nextSorting('tl_article', 'pid', $pageId),
            'tstamp' => time(),
            'title' => $articleData['title'],
            'alias' => $alias,
            'inColumn' => $articleData['inColumn'] ?? 'main',
            'author' => (int) ($articleData['author'] ?? $currentUserId),
            'published' => ($articleData['published'] ?? true) ? '1' : '',
        ];

        $handled = [...self::RESERVED_ARTICLE_FIELDS, 'title', 'alias', 'inColumn', 'author', 'published', 'content'];

        foreach ($articleData as $field => $value) {
            if (in_array($field, $handled, true)) {
                continue;
            }

            $row[$field] = $this->transformValue($value);
        }

        $result = Database::getInstance()
            ->prepare('INSERT INTO tl_article %s')
            ->set($row)
            ->execute();

        $articleId = (int) $result->insertId;

        foreach (array_values($articleData['content'] ?? []) as $contentData) {
            $this->createContent('tl_article', $articleId, $contentData);
        }

        return ['id' => $articleId, 'alias' => $alias, 'title' => $articleData['title']];
    }

    private function createContent(string $ptable, int $pid, array $contentData): int
    {
        $children = $contentData['children'] ?? [];
        unset($contentData['children']);

        $row = [
            'pid' => $pid,
            'ptable' => $ptable,
            'sorting' => $this->nextSorting('tl_content', 'pid', $pid, $ptable),
            'tstamp' => time(),
        ];

        foreach ($contentData as $field => $value) {
            if (in_array($field, self::RESERVED_CONTENT_FIELDS, true)) {
                continue;
            }

            $row[$field] = $this->transformValue($value);
        }

        $result = Database::getInstance()
            ->prepare('INSERT INTO tl_content %s')
            ->set($row)
            ->execute();

        $contentId = (int) $result->insertId;

        foreach (array_values($children) as $childData) {
            $this->createContent('tl_content', $contentId, $childData);
        }

        return $contentId;
    }

    private function nextSorting(string $table, string $pidColumn, int $pid, string|null $ptable = null): int
    {
        $sql = "SELECT MAX(sorting) AS maxSorting FROM $table WHERE $pidColumn = ?";
        $params = [$pid];

        if (null !== $ptable) {
            $sql .= ' AND ptable = ?';
            $params[] = $ptable;
        }

        $row = Database::getInstance()->prepare($sql)->execute(...$params);

        return ((int) $row->maxSorting) + 128;
    }

    /**
     * Arrays are stored the way Contao stores them: as a PHP serialize()
     * string. The one exception is the {"__file__": "<uuid>"} marker, which
     * resolves to the raw binary UUID a fileTree field expects (works both as
     * a scalar value for e.g. singleSRC and nested inside a serialized array,
     * e.g. one row of a multiColumnWizard or one entry of multiSRC).
     */
    private function transformValue(mixed $value): mixed
    {
        if (is_array($value)) {
            if (['__file__'] === array_keys($value)) {
                return StringUtil::uuidToBin((string) $value['__file__']);
            }

            return serialize(array_map($this->transformValue(...), $value));
        }

        if (is_bool($value)) {
            return $value ? '1' : '';
        }

        return $value;
    }
}
