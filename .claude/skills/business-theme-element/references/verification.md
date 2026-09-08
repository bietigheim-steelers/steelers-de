# Prüfen und Demo-Inhalte

Nachschlagewerk für Schritt 6 und 7 der Skill.

## Klassen-Abgleich gegen die Theme-Datei

Der aussagekräftigste Test: stimmen die `class`-Attribute des gerenderten Abschnitts mit
denen der Theme-HTML überein? Dafür liegt ein Skript bei:

```bash
python .claude/skills/business-theme-element/scripts/compare_classes.py \
  --theme "<pfad>/main-files/about-us.html" \
  --theme-marker "data-journey-section" --theme-end "about-us-our-journey end" \
  --rendered /tmp/demo.html \
  --rendered-marker "data-journey-section" --rendered-end "mod_article block" \
  --limit 8
```

Alle Marker sind schlichte Textsuchen. **Start- und Endmarker möglichst symmetrisch wählen**
— am besten auf beiden Seiten dasselbe markante Attribut (`data-journey-section`,
`grid sm:grid-cols-2 lg:grid-cols-3 gap-6`). Ohne Endmarker liest das Skript `--window`
Zeichen weit und schleppt den nächsten Theme-Abschnitt mit ein; die Ausgabe wird dann
unbrauchbar lang.

Verglichen wird über `difflib`, nicht Position gegen Position. Das ist entscheidend, weil
Contao zusätzliche Wrapper einzieht — ein positionsweiser Vergleich wäre ab dem ersten
Zusatz um eine Stelle verschoben und jede weitere Zeile falsch.

Ausgabezeilen:

| | Bedeutung |
|---|---|
| `OK` | identisch |
| `DIFF` | **echter Fehler** — Klasse weicht ab |
| `ORDER` | gleiche Klassen, andere Reihenfolge — für CSS bedeutungslos, kein Fehler |
| `FEHLT` | im gerenderten HTML nicht vorhanden |
| `EXTRA` | nur im gerenderten HTML |

`ORDER` entsteht regelmäßig, weil Templates die Klassenliste bedingt zusammensetzen
(`{{ x ? 'bg-a border-a' : 'bg-b border-b' }} border` schiebt `border` ans Ende). Nicht
hinterherräumen — Tailwind kümmert die Reihenfolge im Attribut nicht.

Exit-Code 1 bei `DIFF` oder `FEHLT`. Die Zahl, auf die es ankommt, ist **`abweichend = 0`**.
`FEHLT` und `EXTRA` haben in dieser Umgebung mehrere harmlose Ursachen:

- **Contao-Wrapper** (`content-business-timeline`, `mod_article block`) und `rte`, das die
  Rich-Text-Komponente an den Einleitungstext hängt → `EXTRA` bzw. `DIFF` mit angehängtem
  `rte`. Kein Theme-Verstoß.
- **Wiederholte Gruppen**: hat das Element mehr Einträge als das Theme (Zeitstrahl mit acht
  statt vier), erscheint jede Folgegruppe als `EXTRA`. Zum Prüfen den Vergleich auf eine
  Gruppe bzw. eine Karte begrenzen.
- **Leere Demo-Daten**: eine `{% if %}`-Bedingung unterdrückt einen Block, weil das Feld
  nicht gefüllt ist. Erst die Daten prüfen, bevor am Template geschraubt wird — beim
  Team-Element war anfangs bei niemandem ein X-Profil hinterlegt, also fehlte durchgehend
  das vierte Social-Icon.
- **Attributreihenfolge an der Schnittkante**: das Theme schreibt `class` vor `src`, Contao
  umgekehrt. Die letzte Zeile vor dem Endmarker kann deshalb auf einer Seite fehlen.

Bei `DIFF`-Zeilen gilt: im Zweifel hat die Theme-Seite recht, auch wenn eine Klasse
überflüssig aussieht. Drei Ausnahmen, die alle schon vorgekommen sind — jede gehört ins
Template als Kommentar, damit der nächste Durchlauf sie nicht „repariert":

- **Tippfehler im Theme.** Die Preiskarte in `services.html` trägt `text-titile-black`. Die
  Klasse existiert im Build gar nicht, richtig ist `text-title_black`. Wenn der Klassen-Check
  eine Klasse meldet, dann im Build gegenprüfen (`grep -cF "  .<klasse> {"`) — kommt sie dort
  nicht vor, ist sie im Theme tot.
- **Wirkungslose `group-hover:`-Varianten.** Das Theme schleppt sie an Karten mit, die gar
  kein `group` tragen.
- **Bewusste Abweichung, weil Contao mehr kann als das Theme-Beispiel.** Die FAQ-Antwort ist
  im Theme ein einzelner `<p>`; aus Contao kommt Rich Text mit mehreren Absätzen, und die
  Tailwind-Preflight nimmt `<p>` die Abstände. Deshalb steht am Antwort-Container zusätzlich
  `space-y-3`. Solche Zusätze sparsam und nur mit Klassen aus dem Build.

Wurde ein Theme-Bestandteil auf Wunsch weggelassen (die Preisliste hat keinen
Monat/Jahr-Umschalter), verschwinden die zugehörigen Klassen mit — `.period` etwa existiert
im Theme nur, damit das JS sie umschreibt. Solche `FEHLT`/`DIFF`-Zeilen sind erwartetes
Ergebnis und keine Nacharbeit.

Whitespace wird normalisiert (das Theme enthält viele bedeutungslose Doppel-Leerzeichen),
Inline-`<svg>` wird übersprungen (`--keep-svg` schaltet das ab).

## Seite abrufen

```bash
curl -s -H "Host: business.localhost" "http://localhost:5388/theme-elemente" > /tmp/demo.html
```

Vorher Cache leeren (als `www-data`, siehe `local-env.md`), sonst kommt die alte Seite aus
dem Contao-Cache.

Schnelle Plausibilitätsprüfung mit Python statt Augenmaß:

```python
import re
s = open('/tmp/demo.html', encoding='utf-8').read()
print('Karten :', s.count('data-sttr-card'))
print('Jahre  :', re.findall(r'text-2xl font-semibold leading-none text-title_black">([^<]*)<', s))
print('picture:', s.count('<picture'))   # soll 0 sein
```

Windows-Terminals geben UTF-8 auf stdout kaputt aus (`1.500 €` wird zu `1.500 ?`). Nicht
darauf hereinfallen — mit `'1.500 €' in s` prüfen statt mit dem Auge.

## Layout im Browser messen

Der Browser-Pane ist oft nicht sichtbar, dann schlägt `screenshot` fehl. `javascript_tool`
funktioniert trotzdem und liefert härtere Zahlen als ein Screenshot:

```js
(() => {
  // GSAP-Einblendung überspringen, sonst misst man den Startzustand der Animation
  document.querySelectorAll('[data-sttr-line], [data-sttr-dots], [data-sttr-card], [data-sttr-wrapper]').forEach(e => {
    e.style.transform = ''; e.style.opacity = ''; e.style.filter = ''; e.style.visibility = 'visible';
  });
  const r = e => (({x,y,width,height}) => ({x: Math.round(x), y: Math.round(y+scrollY), w: Math.round(width), h: Math.round(height)}))(e.getBoundingClientRect());
  const el = document.querySelector('.content-business-timeline');
  return {
    viewport: innerWidth,
    docScrollW: document.documentElement.scrollWidth,   // darf viewport nicht übersteigen
    grid: getComputedStyle(el.querySelector('.grid')).gridTemplateColumns,
    cards: [...el.querySelectorAll('[data-sttr-card]')].map(r),
  };
})()
```

Worauf achten:

- `gridTemplateColumns` — Spaltenzahl je Breakpoint wie im Theme?
- `docScrollW <= innerWidth` — kein horizontaler Überlauf
- Karten gleicher Reihe auf gleicher Höhe (`h-full` im Flex-Container wirkt)
- volle Breite bei Abschnitten mit Hintergrund (`w` = Viewport, nicht Containerbreite)
- `getComputedStyle(el).backgroundColor` gegen die erwartete Theme-Farbe

Mobil über `resize_window` mit Preset `mobile` prüfen. **Danach neu laden**, bevor gemessen
wird: GSAP liest die Viewport-Breite einmal beim Start und backt Transforms ein, die nach
einem Resize nicht mehr passen. Ein `matrix(1, 0, 0, 0, -557, 0)` ohne Neuladen ist dieses
Artefakt, kein Layoutfehler.

`read_console_messages` mit `onlyErrors: true` prüft nebenbei, ob das Theme-JS sauber
durchläuft.

## Demo-Inhalte anlegen

Serialisierte Contao-Felder (Überschrift, cssID, MCW-Zeilen) von Hand als SQL zu schreiben
ist fehleranfällig — besser ein PHP-Skript, das über stdin in den Container läuft:

```bash
docker compose exec -T web php < /pfad/zum/seed.php
```

Gerüst:

```php
<?php
$pdo = new PDO('mysql:host=db;dbname=steelers;charset=utf8mb4', 'steelers', 'steelers',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$headline = static fn (string $v) => serialize(['unit' => 'h2', 'value' => $v]);

function insert(PDO $pdo, string $table, array $data): int
{
    $cols = array_keys($data);
    $pdo->prepare('INSERT INTO `'.$table.'` (`'.implode('`,`', $cols).'`) VALUES ('
        .implode(',', array_fill(0, \count($cols), '?')).')')->execute(array_values($data));

    return (int) $pdo->lastInsertId();
}

$articleId = (int) $pdo->query("SELECT id FROM tl_article WHERE alias='preisliste'
    AND pid=(SELECT id FROM tl_page WHERE alias='theme-elemente')")->fetchColumn();

insert($pdo, 'tl_content', [
    'pid' => $articleId, 'ptable' => 'tl_article', 'sorting' => 256, 'tstamp' => time(),
    'type' => 'business_timeline',
    'headline' => $headline('Vom Förderkreis zum Business Netzwerk'),
    'text' => '<p>Einleitung.</p>',
    'businessLabel' => 'Unsere Geschichte',
    'timelineEntries' => serialize([
        ['year' => '1981', 'text' => 'Gründung'],
    ]),
    'cssID' => serialize(['', '']),
]);
```

Die Demo-Seite ist veröffentlicht, aber aus der Navigation ausgeblendet (`hide = 1`) und hat
zwei Artikel:

| Artikel | Template | Für |
|---|---|---|
| `preisliste` | `mod_article_business_container` | Elemente **ohne** eigenen Container |
| `faq-partner` | (keins) | Module und Elemente **mit** eigenem `<section>` |

Ein neues Element in den passenden der beiden einhängen — welcher, ergibt sich aus der
Rahmen-Entscheidung in Schritt 3 der Skill.

Braucht das Element Bilder, die lokal fehlen: Platzhalter nach `files/business/demo-team/`
(oder einen eigenen Unterordner) kopieren und **nur diesen Teilbaum** registrieren:

```bash
docker compose exec -T -u www-data web php vendor/bin/contao-console contao:filesync files/business
```

UUIDs danach aus `tl_files` holen (`SELECT path, uuid FROM tl_files WHERE path LIKE 'files/business/%'`)
und im Seed-Skript verwenden. Warum unbedingt mit Pfad: siehe `local-env.md`.

Demo-Daten so wählen, dass **jeder optionale Zweig des Templates einmal vorkommt** — sonst
prüft man ihn nie. Beim Team-Element etwa eine Person mit allen vier Social-Links, sonst
bleibt ein Icon ungetestet und der Klassen-Abgleich meldet ein `FEHLT`, das nach
Template-Fehler aussieht.

## Seed-Skripte aufbewahren

Der lokale Setup-Script spielt den Dump neu ein und setzt damit **alle** DB-Änderungen
zurück: neue Spalten, Demo-Seite, Inhaltselemente, Module, FAQ-Kategorien. Der Code auf der
Platte bleibt, die Datenbank nicht.

Deshalb: die Seed-Skripte nicht wegwerfen, sondern im Scratchpad behalten. Nach einem
Setup-Lauf reicht dann

1. `ALTER TABLE … ADD` für die eigenen Spalten (siehe `local-env.md`),
2. `contao:filesync files/business` für eigene Bilddateien,
3. Seed-Skripte erneut ausführen.

Zwei Fallen dabei: Skripte, die vor einer Feldumbenennung geschrieben wurden, brechen mit
`Unknown column` ab — und weil sie mittendrin abbrechen, hinterlassen sie halbe Seitenbäume.
Vor einem erneuten Lauf prüfen, ob Seite, Artikel, Module oder Elemente schon (teilweise)
existieren, sonst legt man Dubletten an.

## Aufräumen

Reine Wegwerf-Testdaten (eigene Testseiten, temporäre Module) nach der Prüfung wieder
löschen. Was auf der Demo-Seite `theme-elemente` liegt, bleibt — die Seite ist der dauerhafte
Schaukasten für alle übernommenen Elemente.

Prüfen, ob beim Seeden Dubletten entstanden sind:

```sql
SELECT c.id, c.pid, c.sorting, c.type
FROM tl_content c JOIN tl_article a ON a.id = c.pid JOIN tl_page p ON p.id = a.pid
WHERE p.alias = 'theme-elemente' ORDER BY a.sorting, c.sorting;
```

Zwei Zeilen mit gleichem `sorting` und `type` sind ein Dublettenpaar.
