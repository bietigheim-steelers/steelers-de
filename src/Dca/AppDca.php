<?php

namespace App\Dca;

use App\Model\AppScreen;
use Contao\ArticleModel;
use Contao\DataContainer;
use Contao\Date;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;

/**
 * Gemeinsame DCA-Bausteine und Callbacks des Backend-Bereichs "App"
 * (contao/dca/tl_app_screen.php, tl_app_block.php, tl_app_flag.php).
 */
class AppDca
{
    /**
     * Felder, die Screens, Bausteine und Schalter gleichermaßen ein- und ausblenden.
     * Muss zu AppAreaMigration::VISIBILITY passen.
     *
     * @return array<string, array>
     */
    public static function visibilityFields(): array
    {
        System::loadLanguageFile('tl_app_common');

        return [
            'published' => [
                'toggle' => true,
                'filter' => true,
                'inputType' => 'checkbox',
                'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 clr'],
                'sql' => ['type' => 'boolean', 'default' => false],
            ],
            'start' => [
                'inputType' => 'text',
                'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
                'sql' => "varchar(10) NOT NULL default ''",
            ],
            'stop' => [
                'inputType' => 'text',
                'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
                'sql' => "varchar(10) NOT NULL default ''",
            ],
            'minAppVersion' => [
                'inputType' => 'text',
                'eval' => ['maxlength' => 20, 'tl_class' => 'w50'],
                'save_callback' => [[self::class, 'validateVersion']],
                'sql' => "varchar(20) NOT NULL default ''",
            ],
            'platform' => [
                'inputType' => 'select',
                'options' => ['ios', 'android'],
                'reference' => &$GLOBALS['TL_LANG']['tl_app_common']['platforms'],
                'eval' => ['includeBlankOption' => true, 'maxlength' => 16, 'tl_class' => 'w50'],
                'sql' => "varchar(16) NOT NULL default ''",
            ],
        ];
    }

    /**
     * Erlaubt leer oder eine Version wie 1.2 / 1.2.0.
     */
    public function validateVersion(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value !== '' && !preg_match('/^\d+\.\d+(\.\d+)?$/', $value)) {
            throw new \RuntimeException($GLOBALS['TL_LANG']['tl_app_common']['versionInvalid'] ?? 'Version wie 1.2.0 angeben.');
        }

        return $value;
    }

    /**
     * Gruppen (kind=group) als Auswahl für das Feld "Gruppe": Schlüssel => Titel.
     *
     * @return array<string, string>
     */
    public function groupOptions(): array
    {
        $options = [];

        foreach (AppScreen::findBy(["kind='group'"], [], ['order' => 'sortOrder ASC, id ASC']) ?? [] as $group) {
            $options[$group->screenKey] = $group->title . ' (' . $group->screenKey . ')';
        }

        return $options;
    }

    /**
     * Zeile in der Screen-Liste: Titel, Schlüssel, Platzierung und Sichtbarkeit.
     */
    public function screenLabel(array $row, string $label): string
    {
        $placements = $GLOBALS['TL_LANG']['tl_app_screen']['placements'];
        $placement = $placements[$row['kind'] === 'group' ? 'menu' : $row['placement']] ?? $row['placement'];
        $kind = $GLOBALS['TL_LANG']['tl_app_screen']['kinds'][$row['kind']] ?? $row['kind'];
        $home = $row['isHome'] ? ' &#9733; Start' : '';
        $parent = ($row['parentKey'] ?? '') !== '' && $row['kind'] !== 'group'
            ? ' &rsaquo; ' . StringUtil::specialchars($row['parentKey'])
            : '';

        return \sprintf(
            '%s <span class="label-info">[%s · %s · %s]%s</span>',
            StringUtil::specialchars($row['title']),
            StringUtil::specialchars($row['screenKey']),
            $placement . $parent,
            $kind,
            $home,
        ) . $this->visibilityHint($row);
    }

    /**
     * Zeile eines Bausteins innerhalb eines Screens.
     */
    public function blockRecord(array $row): string
    {
        $type = $GLOBALS['TL_LANG']['tl_app_block']['types'][$row['blockType']] ?? $row['blockType'];
        $summary = match ($row['blockType']) {
            'text' => StringUtil::substr(strip_tags((string) $row['blockText']), 80),
            'promo' => (string) $row['blockTitle'],
            'button', 'download' => (string) $row['blockLabel'],
            'list' => StringUtil::substr(str_replace("\n", ', ', trim((string) $row['blockItems'])), 80),
            'video' => (string) $row['blockVideoId'],
            'article' => $this->articleLabel((int) $row['blockArticle']),
            'content_element' => $row['blockContent'] ? '#' . $row['blockContent'] : '',
            default => (string) $row['blockTitle'],
        };

        return \sprintf(
            '<div class="tl_content_left"><strong>%s</strong>%s</div>',
            $type,
            $summary !== '' ? ' &ndash; ' . StringUtil::specialchars($summary) : '',
        );
    }

    /**
     * Artikel zum Einbinden: "Seite › Artikel (ID)".
     *
     * @return array<int, string>
     */
    public function articleOptions(): array
    {
        $options = [];

        foreach (ArticleModel::findAll(['order' => 'pid, sorting']) ?? [] as $article) {
            $page = PageModel::findById($article->pid);
            $options[$article->id] = ($page?->title ?? '?') . ' › ' . $article->title . ' (' . $article->id . ')';
        }

        return $options;
    }

    private function articleLabel(int $id): string
    {
        if ($id === 0) {
            return '';
        }

        $article = ArticleModel::findById($id);

        return $article !== null ? (string) $article->title : '#' . $id;
    }

    private function visibilityHint(array $row): string
    {
        $parts = [];

        if (!$row['published']) {
            $parts[] = 'unveröffentlicht';
        }
        if ($row['start'] !== '' || $row['stop'] !== '') {
            $parts[] = trim(
                ($row['start'] !== '' ? 'ab ' . Date::parse('d.m.Y H:i', (int) $row['start']) : '') . ' ' .
                ($row['stop'] !== '' ? 'bis ' . Date::parse('d.m.Y H:i', (int) $row['stop']) : '')
            );
        }
        if (($row['minAppVersion'] ?? '') !== '') {
            $parts[] = 'ab App ' . $row['minAppVersion'];
        }
        if (($row['platform'] ?? '') !== '') {
            $parts[] = $row['platform'];
        }

        return $parts === [] ? '' : ' <span style="color:#999">(' . implode(', ', $parts) . ')</span>';
    }
}
