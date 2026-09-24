---
name: contao-content-import
description: Legt Artikel und Inhaltselemente auf einer bestehenden Seite von steelers.de an, ohne Zugriff auf die Produktionsdatenbank zu brauchen. Nutze diese Skill, wenn der Nutzer eine Parent-/Seiten-ID nennt und will, dass Artikel und Content-Elemente dafür entstehen ("leg mir einen Artikel auf Seite X an", "bau mir folgenden Inhalt auf der Seite ..."), wenn er JSON-Import, Content-Import oder den Backend-Menüpunkt "Inhalte -> Content-Import" erwähnt, oder wenn eine frühere SQL-Import-Idee für Contao-Inhalte gemeint ist (dieser Weg wurde durch den JSON-Importer ersetzt).
---

# Contao Content-Import (JSON, ohne DB-Zugriff)

## Warum dieser Weg, nicht rohes SQL

Auf diesem Rechner gibt es keinen Zugriff auf die Produktionsdatenbank oder
-dateien (siehe AGENTS.md, Abschnitt „Environment"). Ein von Hand
geschriebenes SQL-Insert wäre blind gegenüber existierenden `sorting`-Werten,
belegten `alias`-Werten und einer gültigen `tl_user.id` für `author` — und bei
verschachtelten Elementen (z. B. `element_group`) bräuchte es
`LAST_INSERT_ID()`-Verkettung per Session-Variablen, die bei einem Fehler
mitten in der Produktions-DB aufgeräumt werden müsste.

Stattdessen gibt es einen Importer, der **in** der Zielumgebung läuft:

- Controller: `src/Controller/Backend/ContentImportModule.php`
- Logik: `src/Utils/ContentImporter.php`
- Backend-Menüpunkt: *Inhalte → Content-Import* (nur für Administratoren,
  Prüfung fest im Code, unabhängig von Benutzergruppen-Rechten)
- Registriert in `contao/config/config.php`
  (`$GLOBALS['BE_MOD']['content']['content_import']`)

Der Ablauf für eine Aufgabe ist also: JSON-Datei erzeugen → Nutzer öffnet
*Inhalte → Content-Import* im Backend (lokal **und** auf steelers.de gleich,
nach dem nächsten Deployment) → Datei hochladen oder Text einfügen → Import.
Der Import läuft in einer einzigen Transaktion: entweder wird alles angelegt,
oder nichts — ein fehlgeschlagener Import hinterlässt keine Datenleichen.

## Vorgehen

1. **Seiten-ID vom Nutzer erfragen** (`tl_page.id`), falls nicht schon
   genannt. Alles andere — Artikelstruktur, Inhaltselemente, Texte — wird
   selbstständig entworfen.
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
   vollständige Schema, die `{"__file__": "<uuid>"}`-Konvention für
   Dateireferenzen, wie verschachtelte Elemente (z. B. `element_group`,
   `accordion`) über `children` abgebildet werden, und durchgerechnete
   Beispiele.
4. **Datei-UUIDs beim Nutzer erfragen**, wenn Bilder/Downloads referenziert
   werden sollen — es gibt keinen Zugriff auf `tl_files` der Zielumgebung.
   Der Nutzer findet sie im Backend über die Dateiverwaltung (Rechtsklick →
   Info, oder in der URL beim Bearbeiten einer Datei).
5. **JSON-Datei ablegen** (Projektverzeichnis oder Scratchpad) und dem
   Nutzer die Datei plus eine kurze Anleitung geben: *Inhalte →
   Content-Import* öffnen, Datei hochladen oder Inhalt einfügen, auf
   „importieren" klicken.
6. **Reihenfolge/Sortierung**: neue Artikel und Inhaltselemente werden immer
   ans Ende der Seite bzw. des Artikels angehängt (`MAX(sorting) + 128`).
   Muss etwas dazwischen, sagt man das dem Nutzer — Umsortieren geht danach
   bequem per Drag & Drop im Backend.

## Was NICHT ins JSON gehört

`id`, `pid`, `ptable`, `sorting`, `tstamp` werden vom Importer selbst
gesetzt und in der Validierung ignoriert, falls sie doch mitgeschickt werden.
`author` und `published` haben sinnvolle Defaults (aktueller Backend-User
bzw. veröffentlicht) und müssen nur bei Bedarf überschrieben werden.

## Testen

Es gibt keine automatisierten PHP-Tests (siehe AGENTS.md). Wenn die lokale
Docker-Umgebung läuft, lässt sich die Importer-Logik ohne Backend-Login
direkt prüfen — Bootstrap-Vorlage und Beispielaufruf stehen in
`references/local-testing.md`. Nach einem Test angelegte Datensätze wieder
löschen (`tl_content` vor `tl_article`, wegen der Fremdreferenz).
