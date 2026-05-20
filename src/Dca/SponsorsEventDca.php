<?php

namespace App\Dca;

use Contao\BackendUser;
use Contao\CalendarEventsModel;
use Contao\CalendarModel;
use Contao\ContentModel;
use Contao\DataContainer;
use Contao\Database;
use Contao\Date;
use Contao\PageModel;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * DCA-Callbacks für tl_sponsors_event.
 *
 * Beim Speichern wird automatisch:
 * - ein Zugriffstoken generiert (falls noch nicht vorhanden)
 * - ein tl_calendar_events-Eintrag im Kalender "Sponsor Events" angelegt/aktualisiert
 * - ein tl_content-Element vom Typ "form" für dieses Event angelegt/aktualisiert
 */
class SponsorsEventDca
{
    public function __construct(private readonly ContentUrlGenerator $urlGenerator)
    {
    }

    /**
     * onsubmit_callback: Token generieren, Calendar-Event und Content-Element synchronisieren.
     */
    public function onSubmit(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $db = Database::getInstance();
        $row = $db->prepare("SELECT * FROM tl_sponsors_event WHERE id=?")->execute($dc->id)->fetchAssoc();

        if (!$row) {
            return;
        }

        // 1. Zugriffstoken generieren falls leer
        $token = $row['access_token'];
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("UPDATE tl_sponsors_event SET access_token=? WHERE id=?")->execute($token, $dc->id);
        }

        // 2. "Sponsor Events"-Kalender finden oder anlegen
        $calendar = CalendarModel::findOneBy('title', 'Sponsor Events');
        if ($calendar === null) {
            $calendar = new CalendarModel();
            $calendar->tstamp = time();
            $calendar->title  = 'Sponsor Events';
            $calendar->jumpTo = 0;
            $calendar->save();
        }

        // 3. Calendar-Event anlegen oder aktualisieren
        $calEvent = null;
        if (!empty($row['calendar_event_id'])) {
            $calEvent = CalendarEventsModel::findById((int) $row['calendar_event_id']);
        }

        $startDate = (int) ($row['startDate'] ?? time());

        // Startzeit (HH:ii) mit Datum kombinieren
        $startTime = $startDate;
        if (!empty($row['startTime']) && preg_match('/^(\d{1,2}):(\d{2})$/', $row['startTime'], $m)) {
            $startTime = mktime((int) $m[1], (int) $m[2], 0,
                (int) date('m', $startDate),
                (int) date('d', $startDate),
                (int) date('Y', $startDate)
            );
        }

        if ($calEvent === null) {
            $calEvent            = new CalendarEventsModel();
            $calEvent->pid       = $calendar->id;
            $calEvent->tstamp    = time();
            $calEvent->alias     = 'sponsor-event-' . $dc->id . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
            $calEvent->author    = BackendUser::getInstance()->id;
            $calEvent->startTime = $startTime;
            $calEvent->endTime   = $startDate + 86399;
        }

        $calEvent->title     = $row['title'];
        $calEvent->startDate = $startDate;
        $calEvent->endDate   = $startDate;
        $calEvent->startTime = $startTime;
        $calEvent->teaser    = $row['teaser'] ?? '';
        $calEvent->addImage  = (bool) ($row['addImage'] ?? false);
        $calEvent->singleSRC = $row['singleSRC'] ?? null;
        $calEvent->published = $row['published'] ? '1' : '0';
        $calEvent->save();

        // Referenz speichern falls neu
        if (empty($row['calendar_event_id'])) {
            $db->prepare("UPDATE tl_sponsors_event SET calendar_event_id=? WHERE id=?")
                ->execute($calEvent->id, $dc->id);
        }

        // 4. tl_content vom Typ "form" anlegen oder aktualisieren
        $content = ContentModel::findOneBy(
            ['ptable=? AND pid=? AND type=?'],
            ['tl_calendar_events', $calEvent->id, 'form']
        );

        if ($content === null) {
            $content          = new ContentModel();
            $content->ptable  = 'tl_calendar_events';
            $content->pid     = $calEvent->id;
            $content->type    = 'form';
            $content->sorting = 128;
            $content->tstamp  = time();
        }

        $content->form   = (int) $row['form_id'];
        $content->tstamp = time();
        $content->save();
    }

    /**
     * load_callback für das virtuelle Feld "access_link":
     * Gibt den vollständigen Zugriffslink zurück (wird nicht gespeichert).
     */
    public function generateAccessLink(mixed $value, DataContainer $dc): string
    {
        $db  = Database::getInstance();
        $row = $db->prepare("SELECT access_token FROM tl_sponsors_event WHERE id=?")
            ->execute($dc->id)->fetchAssoc();

        if (empty($row['access_token'])) {
            return '— Bitte zuerst speichern, um den Link zu generieren —';
        }

        $token = $row['access_token'];

        // Zielseite aus dem jumpTo des "Sponsor Events"-Kalenders ermitteln
        $calendar = CalendarModel::findOneBy('title', 'Sponsor Events');
        if ($calendar !== null && (int) $calendar->jumpTo > 0) {
            $page = PageModel::findById((int) $calendar->jumpTo);
            if ($page !== null) {
                $url = $this->urlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL);
                return rtrim($url, '/') . '?token=' . $token;
            }
        }

        return '— Bitte im Kalender "Sponsor Events" eine Zielseite hinterlegen. Token: ' . $token . ' —';
    }

    /**
     * options_callback: Alle verfügbaren Contao-Formulare zur Auswahl anbieten.
     */
    public function getForms(): array
    {
        $forms  = [];
        $result = Database::getInstance()->execute("SELECT id, title FROM tl_form ORDER BY title");

        while ($result->next()) {
            $forms[$result->id] = $result->title;
        }

        return $forms;
    }

    /**
     * label_callback: Listenansicht formatieren (Datum leserlich ausgeben).
     */
    public function formatListLabel(array $row, string $label, DataContainer $dc, array $args): array
    {
        if (!empty($row['startDate'])) {
            $args[1] = Date::parse('d.m.Y', (int) $row['startDate']);
        }

        return $args;
    }
}
