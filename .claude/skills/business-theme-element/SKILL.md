---
name: business-theme-element
description: Übernimmt einen Abschnitt des gekauften SecureVest-HTML-Themes als CMS-gepflegtes Contao-5-Inhaltselement oder Frontend-Modul für business.steelers.de. Nutze diese Skill, sobald der Nutzer auf eine Theme-HTML-Datei zeigt, einen Theme-Abschnitt beim Namen nennt (z. B. "about-us-our-journey", "expert-guidness", "Pricing Plan", "Logo Slider", "FAQ Area", "Client section"), ein neues Business-Element/-Modul wünscht oder ein bestehendes ändern will — auch dann, wenn er das Theme nicht ausdrücklich erwähnt. Das Theme-CSS ist ein fertiger Build, der hier nicht neu erzeugt werden kann; Markup und Klassennamen müssen deshalb auf Anhieb stimmen.
---

# Theme-Abschnitt zu Contao-Element

## Die eine Regel, aus der fast alles Weitere folgt

`files/business/css/style.css` ist ein **fertiger Tailwind-Build aus dem gekauften Theme**.
`npm run build` erzeugt ihn nicht neu — das Projekt hat keinen Tailwind-Quelltext dafür.

Daraus folgt: eine Klasse, die nicht schon in dieser Datei steht, existiert nicht. Erfinde
keine Utilities, kürze keine „redundanten" Klassen weg und ersetze keine Theme-Klasse durch
eine vermeintlich sauberere. Markup aus der Theme-HTML wird **wörtlich** übernommen,
CMS-Werte werden nur an den Stellen eingesetzt, wo im Theme Beispieltext steht.

Vor dem Schreiben eines Templates prüfen, ob jede vorgesehene Klasse im Build vorkommt:

```bash
for c in '.aspect-410\/520 ' '.top-23\.5 ' '.md\:mt-32 '; do printf "%-24s " "$c"; grep -cF "  $c{" files/business/css/style.css; done
```

Der Build ist eingerückt formatiert, deshalb `"  <selector> {"` als Suchmuster. Kommt eine
gebrauchte Klasse nicht vor, ist meist die Responsive-Variante gemeint, die das Theme nie
benutzt hat (`sm:w-auto` existiert, `w-auto` nicht) — dann die Theme-Kombination beibehalten
statt eine eigene zu bauen.

## Schritt 1: Die richtige Form wählen

Woher die Inhalte kommen, entscheidet über die Bauform:

| Inhalte | Bauform | Beispiel aus dem Repo |
|---|---|---|
| Redakteur pflegt sie direkt am Element | Inhaltselement `#[AsContentElement]` | `business_pricing`, `business_timeline`, `business_team` |
| Liegen schon in einer eigenen Tabelle/einem Model | Frontend-Modul `#[AsFrontendModule]` | `partner_slider_module` (nutzt `App\Model\Partners`) |
| Contao-Kern hat die Funktion bereits | Kernmodul + eigenes Template | FAQ → Modul `faqpage` mit Template `mod_faqpage_business` |

Die dritte Zeile zuerst prüfen: für FAQ, News, Formulare, Downloads und Navigation bringt
Contao die Datenhaltung samt Backend mit. Ein eigenes Element wäre doppelte Arbeit und
verlöre Funktionen wie schema.org-Daten oder den Suchindex.

## Schritt 2: Templates am richtigen Ort ablegen

Das ist die Stolperfalle, die im Backend zu *„Could neither find template … nor the legacy
fallback template …"* führt:

- **Inhaltselemente → `templates/content_element/<typ>.html.twig`** (globaler Ordner).
  Das Backend rendert Inhaltselemente in der Vorschau **ohne** Theme-Kontext, ein Template
  unter `templates/business/` wird dort nicht gefunden.
- **Frontend-Module → `templates/business/frontend_module/<typ>.html.twig`.**
  Module rendern nur im Frontend, dort ist der Theme-Kontext aktiv.
- **Kernmodul-Templates → `templates/mod_<name>_business.html.twig`** (globaler Ordner,
  im Backend über *Template* auswählbar).

Ein `{% include 'business/icon.html.twig' %}` ist davon nicht betroffen — das ist ein
direkter Twig-Pfad ab `templates/` und funktioniert auch ohne Theme-Kontext.

## Schritt 3: Rahmen entscheiden

Artikel bringen den äußeren Rahmen mit: `mod_article_business_container` liefert
`<section class="section-spacing-lg-md"><div class="container">`, `mod_article_business` nur
den Abschnitt ohne Container.

- **Normalfall: kein eigener `<section>`/`.container` im Element** — so machen es
  `business_stats`, `business_pricing` und `business_timeline`. Das Element beginnt direkt
  mit seinem Inhalt.
- **Ausnahme: der Theme-Abschnitt hat einen vollflächigen Hintergrund** (z. B.
  `bg-secondary` bei `expert-guidness`). Dann muss das Element seinen `<section>` selbst
  mitbringen, sonst endet der Hintergrund an der Containerkante. Umsetzung siehe
  `business_team`: `{% block wrapper_tag %}section{% endblock %}` plus
  `{% set attributes = attrs().addClass('section-spacing-lg bg-secondary') %}`.
  Solche Elemente gehören in einen Artikel **ohne** Container-Template — das gehört in die
  Feldbeschreibung im Backend, sonst tappt der Redakteur hinein.

Frontend-Module stehen dagegen oft direkt in einem Layout-Bereich und bringen `<section>`
und `.container` immer selbst mit.

## Schritt 4: Feste Stückzahlen im Theme erkennen

Manche Theme-Abschnitte sind auf eine **exakte Anzahl Elemente** hin von Hand gebaut. Der
Zeitstrahl ist das Musterbeispiel: vier Spalten mit waagerechter Linie am Desktop, zwei
Spalten mit senkrechter Linie mobil — und alle vier Positionen tragen unterschiedliche
Klassen (`md:mt-32` vs. `md:-mt-8`, Pfeil oben vs. unten, `pt-0` vs. `pt-10`, `top-14` vs.
`top-23.5`).

Die Versuchung ist, das zu einem generischen Raster umzuschreiben. Das bricht das Design.
Stattdessen: **die Liste im Controller in Gruppen der Theme-Größe zerlegen** (`array_chunk`)
und pro Gruppe den Theme-Block einmal komplett ausgeben. Die Position innerhalb der Gruppe
wählt dann die passende Theme-Variante aus. Vorher in `files/business/js/animation.js`
nachsehen, ob der Animations-Hook mehrere Instanzen verträgt — `data-journey-section` wird
per `querySelectorAll` verarbeitet, also ja.

Genauso bei Endlos-Bändern: `.marquee-slider` in `files/business/js/main.js` dupliziert den
Inhalt genau **einmal** und springt bei halber Scrollbreite zurück. Ist ein Durchlauf
schmaler als der Viewport, klafft eine Lücke — also im Controller so oft wiederholen, bis
eine Mindestanzahl erreicht ist (siehe `PartnerSliderModule::MIN_ITEMS`).

## Schritt 5: Bauen

Die konkreten Muster — Controller-Gerüst, MultiColumnWizard-Felder, Insert-Tag-Auflösung,
Bilder, Icons, der wiederkehrende Abschnittskopf — stehen in
**`references/contao-plumbing.md`**. Dort nachschlagen, statt aus dem Gedächtnis zu bauen;
mehrere Details sind kontraintuitiv (z. B. dass `text` einem eigenen Inhaltselement nicht
automatisch zur Verfügung steht).

Zur Vollständigkeit gehören außerdem:

- DCA-Felder und Palette in `contao/dca/tl_content.php` bzw. `contao/dca/tl_module.php`
- deutsche Labels in `contao/languages/de/tl_content.php` bzw. `.../modules.php`,
  inklusive `CTE`/`FMD`-Eintrag mit Kurzbeschreibung
- neue Spalten in der Datenbank (siehe `references/local-env.md` — **nicht** einfach
  `contao:migrate` laufen lassen)

## Schritt 6: Prüfen

Zwei Prüfungen, die sich in dieser Reihenfolge ergänzen:

**Klassen-Abgleich gegen die Theme-Datei.** Das ist der Beweis, dass nichts verrutscht ist:
`scripts/compare_classes.py` sammelt die `class`-Attribute beider Seiten ein und vergleicht
sie über `difflib`. Aufruf, Markerwahl und wie `FEHLT`/`EXTRA` zu lesen sind, stehen in
`references/verification.md`. Kurzfassung: **`abweichend = 0`** ist das Ziel; `EXTRA` sind
meist Contao-Wrapper oder Wiederholungsgruppen und kein Theme-Verstoß.

**Layout im Browser messen.** Nicht raten, ob das Raster stimmt: über
`mcp__Claude_Browser__javascript_tool` `gridTemplateColumns`, `getBoundingClientRect()` und
`document.documentElement.scrollWidth` auslesen — desktop und mobil. Auch das steht mit
fertigem Schnipsel in `references/verification.md`.

Wichtig bei der Browser-Messung: die GSAP-Einblendungen setzen Inline-Transforms
(`scaleX: 0`, `opacity: 0`) und feuern nur beim Scrollen. Im nicht sichtbaren Browser-Pane
laufen sie nie an. Zum Messen des Ruhezustands die Inline-Styles kurz leeren — sonst misst
man den Animations-Startzustand und hält ihn für einen Fehler.

## Schritt 7: Demo-Inhalte anlegen

Lokal dürfen Testdaten angelegt werden, und ein Element ohne Inhalt lässt sich nicht
beurteilen. Die Demo-Seite `business.localhost/theme-elemente` sammelt alle bisher
übernommenen Elemente — neue dort ergänzen, statt eine weitere Seite zu bauen.

Inhalte per PHP-Skript in die DB schreiben (serialisierte Felder von Hand zu tippen ist
fehleranfällig), Beispiel und Aufrufweise in `references/verification.md`. Danach
Cache leeren und die Seite abrufen.

## Landminen der lokalen Umgebung

Fünf Dinge, die hier still und heimlich Schaden anrichten — Details und Rettungswege in
**`references/local-env.md`**:

1. Console-Befehle **immer** als `www-data` (`docker compose exec -u www-data web …`).
   Sonst gehört `var/cache/prod` danach root und die **gesamte Seite** antwortet mit einem
   leeren HTTP 500 ohne Log-Eintrag.
2. `contao:migrate` nie durchlaufen lassen — der Datenbestand enthält Spalten längst
   entfernter Extensions, die dabei alle gelöscht würden. Stattdessen `--dry-run` und nur
   das benötigte `ALTER TABLE … ADD` ausführen.
3. `contao:filesync` nie ohne Pfad — die meisten Dateien fehlen lokal, ein voller Lauf
   löscht ~3000 `tl_files`-Zeilen und zerstört alle UUID-Referenzen.
4. Der Setup-Script des Nutzers spielt den Dump neu ein und setzt damit alle DB-Änderungen
   zurück — Spalten, Demo-Seite, Elemente, Module. Der Code bleibt. Seed-Skripte deshalb
   aufbewahren und vor dem erneuten Lauf auf Reste prüfen, sonst entstehen Dubletten.
5. Der DB-Client im Container heißt `mariadb`, nicht `mysql`.

Und harmlos, aber leicht misszuverstehen: Dateien unter `files/steelers/content/` fehlen
lokal. `figure()` rendert dann `<img src alt>` ohne Quelle. Das ist ein Datenproblem, kein
Template-Fehler — das bestehende Partnerverzeichnis verhält sich genauso.

## Was am Ende in AGENTS.md gehört

`AGENTS.md` ist die dauerhafte Projektdoku. Nach jedem übernommenen Abschnitt dort ergänzen:
Elementtyp, Controller- und Template-Pfad, die Rahmen-Entscheidung aus Schritt 3 und alles,
was am Theme-Markup nicht offensichtlich ist (feste Stückzahlen, Animations-Hooks,
übersprungene Datensätze). Neue Icons in `templates/business/icon.html.twig` mit auflisten.
