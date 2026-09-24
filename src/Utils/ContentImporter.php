<?php

namespace App\Utils;

use Contao\ArticleModel;
use Contao\Controller;
use Contao\Database;
use Contao\StringUtil;

/**
 * Creates tl_content rows on a given, already existing article from a JSON
 * payload (see .claude/skills/contao-content-import for the schema and
 * generation rules). The article is always passed in explicitly by the
 * caller (the content list is always opened for one specific article),
 * never taken from the JSON.
 *
 * Runs inside one DB transaction: either every element is created, or none
 * is. Field names and the "type" of every content element are validated
 * against the live tl_content DCA before anything is written, so a typo is
 * reported instead of silently failing or corrupting the target article.
 */
class ContentImporter
{
    private const RESERVED_CONTENT_FIELDS = ['id', 'pid', 'ptable', 'sorting', 'tstamp', 'children'];

    /**
     * @return list<array{id:int, type:string}>
     */
    public function import(string $json, int $articleId): array
    {
        try {
            $elements = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Ungültiges JSON: ' . $e->getMessage());
        }

        if (!is_array($elements)) {
            throw new \RuntimeException('Das JSON muss ein Array von Inhaltselementen sein.');
        }

        if (!ArticleModel::findById($articleId)) {
            throw new \RuntimeException("Artikel $articleId existiert nicht.");
        }

        Controller::loadDataContainer('tl_content');

        $errors = $this->validate($elements);

        if ($errors) {
            throw new \RuntimeException(implode("\n", $errors));
        }

        $db = Database::getInstance();
        $db->query('START TRANSACTION');

        try {
            $created = [];

            foreach (array_values($elements) as $elementData) {
                $id = $this->createContent('tl_article', $articleId, $elementData);
                $created[] = ['id' => $id, 'type' => $elementData['type']];
            }

            $db->query('COMMIT');
        } catch (\Throwable $e) {
            $db->query('ROLLBACK');
            throw $e;
        }

        return $created;
    }

    /**
     * @return list<string>
     */
    private function validate(array $elements): array
    {
        if (!$elements) {
            return ['Das JSON enthält keine Inhaltselemente.'];
        }

        $validTypes = array_diff(
            array_keys($GLOBALS['TL_DCA']['tl_content']['palettes'] ?? []),
            ['__selector__', 'default']
        );

        $contentFields = array_keys($GLOBALS['TL_DCA']['tl_content']['fields'] ?? []);

        $errors = [];

        foreach (array_values($elements) as $ei => $element) {
            $errors = [
                ...$errors,
                ...$this->validateContent('Element ' . ($ei + 1), $element, $validTypes, $contentFields),
            ];
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

    private function createContent(string $ptable, int $pid, array $contentData): int
    {
        $children = $contentData['children'] ?? [];
        unset($contentData['children']);

        $row = [
            'pid' => $pid,
            'ptable' => $ptable,
            'sorting' => $this->nextSorting($pid, $ptable),
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

    private function nextSorting(int $pid, string $ptable): int
    {
        $row = Database::getInstance()
            ->prepare('SELECT MAX(sorting) AS maxSorting FROM tl_content WHERE pid = ? AND ptable = ?')
            ->execute($pid, $ptable);

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
