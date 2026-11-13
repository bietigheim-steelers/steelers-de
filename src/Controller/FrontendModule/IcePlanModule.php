<?php

declare(strict_types=1);

namespace App\Controller\FrontendModule;

use App\IcePlan\IcePlanProvider;
use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\FilesModel;
use Contao\ModuleModel;
use Contao\StringUtil;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Zeigt den Eisplan (Training, Spiele, sonstige Belegung) direkt aus den woechentlich
 * hochgeladenen PDFs an. Im Modul ist nur der Ordner gewaehlt, in den die PDFs hochgeladen werden.
 */
#[AsFrontendModule(type: 'ice_plan_module', category: 'steelers_season_modules')]
class IcePlanModule extends AbstractFrontendModuleController
{
    public function __construct(
        private readonly IcePlanProvider $provider,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ModuleModel $model, Request $request): Response
    {
        $folder = $model->iceplan_folder ? FilesModel::findByUuid($model->iceplan_folder) : null;
        $weeks = $folder ? $this->provider->weeks($this->projectDir . '/' . $folder->path) : [];

        $selected = null;
        if ($weeks !== []) {
            $requested = (string) $request->query->get('kw', '');
            $currentKey = (new \DateTimeImmutable('today'))->format('o-W');
            $selected = $weeks[$requested] ?? $weeks[$currentKey] ?? null;
            if ($selected === null) {
                // Aktuelle Woche fehlt: naechste kommende, sonst die juengste.
                foreach ($weeks as $week) {
                    if ($week['key'] >= $currentKey) {
                        $selected = $week;
                        break;
                    }
                }
                $selected ??= end($weeks);
            }
        }

        $days = $selected ? $this->provider->plan($selected['path']) : null;

        // Teamfilter: Titel auf Teamnamen zusammenfassen ("U20 Skills/TW" -> U20, "SH Grün" -> SH,
        // "SL-Nachw. Weiß" -> SL-Nachw.). Alles andere (Hobbygruppen, Veranstaltungen) bleibt
        // sichtbar, bekommt aber keinen eigenen Filter.
        $teams = [];
        $days ??= [];
        foreach ($days as &$day) {
            // Die Zeiten der Profis (Steelers) stehen nicht im oeffentlichen Plan; Spiele bleiben.
            $day['entries'] = array_values(array_filter(
                $day['entries'],
                static fn (array $e): bool => !($e['type'] === 'training' && $e['title'] === 'Steelers'),
            ));
            foreach ($day['entries'] as &$entry) {
                $entry['team'] = '';
                if ($entry['type'] === 'other') {
                    continue;
                }
                if (!empty($entry['hobby'])) {
                    // Im PDF orange hinterlegt und mit Kabinen: Hobbymannschaft.
                    $entry['team'] = 'Hobbymannschaften';
                } elseif (preg_match('/\bU\s?(\d+)\b/u', $entry['title'], $m)) {
                    $entry['team'] = 'U' . $m[1];
                } elseif (preg_match('/^(Frauen|1b|LS|TW|SH|SL-Nachw\.)(?=$|[\s-])/u', $entry['title'], $m)) {
                    $entry['team'] = [
                        'SH' => 'Steelers Hobby',
                        'LS' => 'Laufschule',
                        'SL-Nachw.' => 'Schlägerlauf',
                    ][$m[1]] ?? $m[1];
                }
                if ($entry['team'] !== '') {
                    $teams[$entry['team']] = $entry['team'];
                }
            }
            unset($entry);
        }
        unset($day);
        uksort($teams, static function (string $a, string $b): int {
            $ua = preg_match('/^U(\d+)$/', $a, $ma);
            $ub = preg_match('/^U(\d+)$/', $b, $mb);

            if ($a === 'Hobbymannschaften' || $b === 'Hobbymannschaften') {
                return ($a === 'Hobbymannschaften') <=> ($b === 'Hobbymannschaften');
            }

            return $ua && $ub ?(int) $ma[1] <=> (int) $mb[1] : (($ub <=> $ua) ?: strnatcasecmp($a, $b));
        });
        $template->teams = array_values($teams);

        $keys = array_keys($weeks);
        $index = $selected ? array_search($selected['key'], $keys, true) : false;

        // Contao speichert die Ueberschrift serialisiert: a:2:{unit, value}.
        $headline = StringUtil::deserialize($model->headline, true);
        $template->headline = $headline['value'] ?? '';
        $template->headlineUnit = ($headline['unit'] ?? '') ?: 'h2';
        $template->cssId = $model->cssID[0] ?? '';
        $template->cssClass = $model->cssID[1] ?? '';
        $template->hasFolder = (bool) $folder;
        $template->week = $selected;
        $template->days = $days;
        $template->parseFailed = $selected !== null && $days === [];
        $template->today = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $template->pdfUrl = $selected ? '/' . $folder->path . '/' . rawurlencode($selected['file']) : null;
        $template->prev = $index !== false && $index > 0 ? $weeks[$keys[$index - 1]] : null;
        $template->next = $index !== false && $index < count($keys) - 1 ? $weeks[$keys[$index + 1]] : null;
        $template->baseUrl = $request->getPathInfo();

        return $template->getResponse();
    }
}
