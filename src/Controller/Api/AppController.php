<?php

namespace App\Controller\Api;

use App\Api\ApiResponder;
use App\Api\AppBlockBuilder;
use App\Api\AppVisibility;
use App\Api\ContentFormatter;
use App\Api\ImageUrlBuilder;
use App\Model\AppBlock;
use App\Model\AppFlag;
use App\Model\AppScreen;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aufbau der App aus dem Backend-Bereich "App": /app/config (Navigation, Schalter) und
 * /app/screens/{key} (Bausteine). Vertrag: docs/openapi.yaml.
 */
class AppController extends AbstractApiController
{
    public function __construct(
        ContaoFramework $framework,
        ApiResponder $responder,
        ImageUrlBuilder $images,
        ContentFormatter $format,
        private readonly AppVisibility $visibility,
        private readonly AppBlockBuilder $blocks,
    ) {
        parent::__construct($framework, $responder, $images, $format);
    }

    public function config(Request $request): Response
    {
        $this->boot();

        [$platform, $appVersion] = $this->client($request);
        $time = time();

        $tabs = [];
        $menu = [];
        $children = []; // Gruppenschlüssel => Untereinträge
        $groupIndex = []; // Gruppenschlüssel => Position in $menu
        $home = null;
        $firstScreen = null;
        $revision = 0;

        $screens = AppScreen::findBy(
            [$this->publishedCondition(AppScreen::getTable(), $time)],
            [],
            ['order' => 'sortOrder ASC, id ASC'],
        );

        foreach ($screens ?? [] as $screen) {
            if (!$this->visibility->isVisible($screen, $platform, $appVersion)) {
                continue;
            }

            $item = $this->navItem($screen);
            if ($item === null) {
                continue;
            }

            $revision = max($revision, (int) $screen->tstamp);

            if ($screen->kind === 'screen') {
                $firstScreen ??= $screen->screenKey;
                if ($screen->isHome && $home === null) {
                    $home = $screen->screenKey;
                }
            }

            // Eintrag mit Gruppe: gehört in deren Untermenü, unabhängig von der Platzierung.
            $parentKey = $screen->kind === 'group' ? '' : (string) $screen->parentKey;
            if ($parentKey !== '') {
                $children[$parentKey][] = $item;
                continue;
            }

            if ($screen->kind === 'group') {
                // Gruppen gibt es nur im Menü (ein Tab muss direkt etwas öffnen).
                $groupIndex[$item['key']] = \count($menu);
                $menu[] = $item;
                continue;
            }

            match ($screen->placement) {
                'tab' => $tabs[] = $item,
                'menu' => $menu[] = $item,
                default => null,
            };
        }

        // Kinder an ihre Gruppen hängen. Kinder unsichtbarer oder fehlender Gruppen entfallen,
        // Gruppen ohne Kinder werden nicht ausgeliefert.
        foreach ($groupIndex as $key => $position) {
            $menu[$position]['children'] = $children[$key] ?? [];
        }
        $menu = array_values(array_filter(
            $menu,
            static fn (array $i): bool => $i['type'] !== 'group' || $i['children'] !== [],
        ));

        if ($home === null) {
            $home = $this->firstTabScreen($tabs) ?? $firstScreen ?? '';
        }

        $flags = [];
        $flagRows = AppFlag::findBy([$this->publishedCondition(AppFlag::getTable(), $time)], [], ['order' => 'flagKey ASC']);
        foreach ($flagRows ?? [] as $flag) {
            if ($this->visibility->isVisible($flag, $platform, $appVersion)) {
                $flags[$flag->flagKey] = $this->flagValue((string) $flag->flagValue);
                $revision = max($revision, (int) $flag->tstamp);
            }
        }

        return $this->responder->json($request, [
            'revision' => $revision,
            'home' => $home,
            'tabs' => $tabs,
            'menu' => $menu,
            'flags' => (object) $flags,
        ], [], 60);
    }

    public function screen(Request $request, string $key): Response
    {
        $this->boot();

        [$platform, $appVersion] = $this->client($request);
        $time = time();

        $screen = AppScreen::findOneBy(
            [$this->publishedCondition(AppScreen::getTable(), $time), 'screenKey=?', "kind='screen'"],
            [$key],
        );

        if ($screen === null || !$this->visibility->isVisible($screen, $platform, $appVersion)) {
            return $this->responder->error(404, 'not_found', 'Screen nicht gefunden.');
        }

        $blocks = [];
        $updatedAt = (int) $screen->tstamp;

        $rows = AppBlock::findBy(
            [$this->publishedCondition(AppBlock::getTable(), $time), 'pid=?'],
            [(int) $screen->id],
            ['order' => 'sorting ASC, id ASC'],
        );

        foreach ($rows ?? [] as $row) {
            if (!$this->visibility->isVisible($row, $platform, $appVersion)) {
                continue;
            }
            $updatedAt = max($updatedAt, (int) $row->tstamp);
            array_push($blocks, ...$this->blocks->build($row));
        }

        return $this->responder->json($request, [
            'key' => (string) $screen->screenKey,
            'title' => $this->format->text($screen->title) ?? '',
            'updatedAt' => $this->format->date($updatedAt),
            'blocks' => $blocks,
        ], [], 60);
    }

    /**
     * @return array{0: string|null, 1: string|null} Plattform und App-Version aus der Anfrage
     */
    private function client(Request $request): array
    {
        return [
            AppVisibility::platform($request->query->get('platform')),
            AppVisibility::version($request->query->get('appVersion')),
        ];
    }

    private function navItem(AppScreen $screen): ?array
    {
        $url = $screen->kind === 'link' ? $this->format->url($screen->url) : null;
        if ($screen->kind === 'link' && $url === null) {
            return null;
        }

        return [
            'key' => (string) $screen->screenKey,
            'title' => $this->format->text($screen->title) ?? '',
            'icon' => $this->format->text($screen->icon),
            'type' => \in_array($screen->kind, ['native', 'link', 'group'], true) ? $screen->kind : 'screen',
            'url' => $url,
        ];
    }

    /**
     * @param list<array> $tabs
     */
    private function firstTabScreen(array $tabs): ?string
    {
        foreach ($tabs as $tab) {
            if ($tab['type'] === 'screen') {
                return $tab['key'];
            }
        }

        return null;
    }

    /**
     * "ja"/"1"/"true" → true, "nein"/"0"/"false"/leer → false, Zahlen als Zahl, sonst Text.
     */
    private function flagValue(string $value): bool|int|float|string
    {
        $value = trim($value);
        $lower = mb_strtolower($value);

        if (\in_array($lower, ['1', 'true', 'ja', 'an', 'on', 'yes'], true)) {
            return true;
        }
        if (\in_array($lower, ['', '0', 'false', 'nein', 'aus', 'off', 'no'], true)) {
            return false;
        }
        if (is_numeric($value)) {
            return $value + 0;
        }

        return $value;
    }
}
