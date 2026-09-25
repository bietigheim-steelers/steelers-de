---
name: contao-content-import
description: Legt Inhaltselemente in einem bestehenden Artikel von steelers.de an, ohne Zugriff auf die Produktionsdatenbank zu brauchen. Nutze diese Skill, wenn der Nutzer eine Artikel-ID nennt und will, dass Inhaltselemente dafür entstehen ("bau mir folgenden Inhalt in Artikel X", "leg diese Elemente in den Artikel..."), wenn er das Content-Import-Icon im Artikelbaum, JSON-Import für Contao-Inhalte erwähnt, oder wenn eine frühere SQL-Import-Idee für Contao-Inhalte gemeint ist (dieser Weg wurde durch den JSON-Importer ersetzt).
---

# Contao Content-Import (JSON, ohne DB-Zugriff)

## Warum dieser Weg, nicht rohes SQL

Auf diesem Rechner gibt es keinen Zugriff auf die Produktionsdatenbank oder
-dateien (siehe AGENTS.md, Abschnitt „Environment"). Ein von Hand
geschriebenes SQL-Insert wäre blind gegenüber existierenden `sorting`-Werten
im Zielartikel — und bei verschachtelten Elementen (z. B. `element_group`)
bräuchte es `LAST_INSERT_ID()`-Verkettung per Session-Variablen, die bei
einem Fehler mitten in der Produktions-DB aufgeräumt werden müsste.

Stattdessen gibt es einen Importer, der **in** der Zielumgebung läuft, als
Icon direkt neben dem Artikel im Artikelbaum einer Seite — dort, wo man
ohnehin schon einen bestimmten Artikel vor sich hat:

- Icon: *Content-Import* neben jedem Artikel unter `contao?do=article&id=<Seite>`
  (die Ansicht, in der eine Seite ihre Artikel als Baum zeigt), definiert in
  `contao/dca/tl_article.php` (`list.operations.content_import`) — eine
  Zeilen-Operation wie das „Artikel"-Icon, das jede Seite in `tl_page.php`
  bekommt, nicht eine globale Operation auf der Liste.
- Handler: `src/Controller/Backend/ContentImportAction.php`, aufgerufen über
  Contaos `key=`-Mechanismus (`$GLOBALS['BE_MOD']['content']['article']['content_import']`
  in `contao/config/config.php`) — nur für Administratoren, Prüfung fest im
  Code, unabhängig von Benutzergruppen-Rechten
- Logik: `src/Utils/ContentImporter.php`

Der Parent ist dabei **immer** der angeklickte Artikel (`Input::get('id')`,
von Contao beim Klick automatisch auf die ID dieser Zeile gesetzt) — kein
Seiten-Kontext nötig, das JSON beschreibt ausschließlich Inhaltselemente
(keine Artikel, keine Seiten-ID).

**Wichtig, falls hier je etwas geändert wird:** Das Icon muss eine
Zeilen-Operation auf `tl_article` bleiben, keine globale Operation auf
`tl_content`. Mit einem `key`-Parameter in der URL behandelt Contaos
`DcaUrlAnalyzer::findTableAndId()` die `id` als Datensatz-ID der *aktuellen*
Tabelle, nicht als Parent-Referenz. `tl_content` hat ein dynamisches
`ptable`; eine dort übergebene Artikel-ID würde fälschlich als
Content-Element-ID gelesen, und das Backend-Chrome/Breadcrumb stürzt beim
Rendern ab (`Parent record of tl_content.<id> not found`). Genau das ist
beim ersten Versuch passiert. `tl_article` hat kein dynamisches `ptable`,
deshalb funktioniert derselbe Mechanismus dort.

Der Ablauf für eine Aufgabe ist also: Nutzer öffnet im Backend die
Artikelliste der Zielseite (`contao?do=article&id=<Seite>`) → klickt beim
gewünschten Artikel auf das Content-Import-Icon → JSON-Datei hochladen oder
Text einfügen → Import. Bei Erfolg landet man in der Inhaltselement-Liste
dieses Artikels (`contao?do=article&table=tl_content&id=<Artikel>`, ohne
`key`-Parameter also unproblematisch), die neuen Elemente erscheinen sofort.
Der Import läuft in einer einzigen Transaktion: entweder wird alles angelegt,
oder nichts — ein fehlgeschlagener Import hinterlässt keine Datenleichen.

## Vorgehen

1. **Artikel-ID oder zumindest die Seite vom Nutzer erfragen**, falls nicht
   schon genannt — nur um zu sagen, wo das Icon zu finden ist
   (`contao?do=article&id=<Seite>`, dort beim gewünschten Artikel auf das
   Content-Import-Icon klicken). Existiert der Artikel noch nicht, muss er
   zuerst ganz normal im Backend angelegt werden (das übernimmt dieser
   Importer bewusst nicht — er ergänzt Inhaltselemente in einem bereits
   vorhandenen Artikel). Die Artikel-ID steht **nicht** im JSON. Alles
   andere — die Inhaltselemente selbst, Texte, Struktur — wird selbstständig
   entworfen.
2. **Für jeden geplanten Inhaltselement-Typ die echten Feldnamen
   nachschlagen**, nicht aus dem Gedächtnis raten:
   - Core-Typen: `vendor/contao/core-bundle/contao/dca/tl_content.php`
   - Projekteigene Felder/Typen (z. B. `business_pricing`,
     `business_timeline`, `business_team`, `teamMembers`, footer-Elemente):
     `contao/dca/tl_content.php`
   - Deutsche Feldlabels/Beschreibungen: `contao/languages/de/tl_content.php`
   - Der Importer validiert Feldnamen und `type` ohnehin gegen die
     tatsächlich geladene DCA (siehe unten) und bricht mit einer klaren
     Fehlermeldung ab statt etwas Falsches zu schreiben — trotzdem lieber
     vorher nachschlagen als sich auf die Fehlermeldung zu verlassen.
   - Für ein Element aus dem Business-Theme zusätzlich die Skill
     `business-theme-element` konsultieren.
3. **JSON gemäß `references/json-schema.md` schreiben.** Dort stehen das
   vollständige Schema (ein Array von Inhaltselementen, keine Hülle mit
   Artikel- oder Seiten-ID), die `{"__file__": "<uuid>"}`-Konvention für
   Dateireferenzen, wie verschachtelte Elemente (z. B. `element_group`,
   `accordion`) über `children` abgebildet werden, und durchgerechnete
   Beispiele.
4. **Datei-UUIDs beim Nutzer erfragen**, wenn Bilder/Downloads referenziert
   werden sollen — es gibt keinen Zugriff auf `tl_files` der Zielumgebung.
   Der Nutzer findet sie im Backend über die Dateiverwaltung (Rechtsklick →
   Info, oder in der URL beim Bearbeiten einer Datei).
5. **JSON-Datei ablegen** (Projektverzeichnis oder Scratchpad) und dem
   Nutzer die Datei plus eine kurze Anleitung geben: die Artikelliste der
   Zielseite öffnen (`contao?do=article&id=<Seite>`), beim Zielartikel auf
   das Content-Import-Icon klicken, Datei hochladen oder Inhalt einfügen,
   auf „importieren" klicken.
6. **Reihenfolge/Sortierung**: neue Inhaltselemente werden immer ans Ende
   des Artikels angehängt (`MAX(sorting) + 128`). Muss etwas dazwischen,
   sagt man das dem Nutzer — Umsortieren geht danach bequem per Drag & Drop
   im Backend.

## Was NICHT ins JSON gehört

Weder eine Artikel- noch eine Seiten-ID gehören ins JSON (der Artikel kommt
aus dem URL-Kontext des Buttons). `id`, `pid`, `ptable`, `sorting`, `tstamp`
werden vom Importer selbst gesetzt und in der Validierung ignoriert, falls
sie doch mitgeschickt werden.

## Testen

Es gibt keine automatisierten PHP-Tests (siehe AGENTS.md). Wenn die lokale
Docker-Umgebung läuft, lässt sich die Importer-Logik ohne Backend-Login
direkt prüfen — Bootstrap-Vorlage und Beispielaufruf stehen in
`references/local-testing.md`. Nach einem Test angelegte Datensätze wieder
löschen (verschachtelte Kinder zuerst, dann die Wurzel-Elemente).
