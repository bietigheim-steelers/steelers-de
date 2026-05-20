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
use Symfony\Component\HttpFoundation\RequestStack;

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
    public function __construct(private readonly RequestStack $requestStack)
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

        if ($calEvent === null) {
            $calEvent            = new CalendarEventsModel();
            $calEvent->pid       = $calendar->id;
            $calEvent->tstamp    = time();
            $calEvent->alias     = 'sponsor-event-' . $dc->id . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
            $calEvent->author    = BackendUser::getInstance()->id;
            $calEvent->startTime = $startDate;
            $calEvent->endTime   = $startDate + 86399;
        }

        $calEvent->title     = $row['title'];
        $calEvent->startDate = $startDate;
        $calEvent->endDate   = $startDate;
        $calEvent->published = $row['published'] ? '1' : '';
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
        $row = $db->prepare("SELECT access_token, link_page FROM tl_sponsors_event WHERE id=?")
            ->execute($dc->id)->fetchAssoc();

        if (empty($row['access_token'])) {
            return '— Bitte zuerst speichern, um den Link zu generieren —';
        }

        $token = $row['access_token'];

        if (!empty($row['link_page'])) {
            $page = PageModel::findById((int) $row['link_page']);
            if ($page !== null) {
                $page->loadDetails();
                $baseUrl = $page->getAbsoluteUrl();
                return rtrim($baseUrl, '/') . '?token=' . $token;
            }
        }

        // Fallback: aktuellen Host verwenden
        $request = $this->requestStack->getCurrentRequest();
        if ($request !== null) {
            return $request->getSchemeAndHttpHost() . '/sponsor-anmeldung?token=' . $token;
        }

        return $token;
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
