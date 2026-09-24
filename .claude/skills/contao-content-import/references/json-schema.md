# JSON-Schema für den Content-Import

Wird von `App\Utils\ContentImporter::import()` gelesen. Grundprinzip: **jedes
Feld, das keine Sonderbehandlung hat, wird 1:1 als Spaltenname in `tl_article`
bzw. `tl_content` übernommen.** Es gibt also keine feste Liste erlaubter
Felder pro Elementtyp im Code — die Validierung prüft stattdessen live gegen
die geladene DCA (`$GLOBALS['TL_DCA']['tl_article'/'tl_content']['fields']`).
Ein Tippfehler im Feldnamen führt zu einer Fehlermeldung, nicht zu einem
stillen Fehlschlag.

## Grundgerüst

```json
{
  "page_id": 185,
  "articles": [
    {
      "title": "Artikeltitel",
      "content": [
        { "type": "headline", "headline": { "value": "Überschrift", "unit": "h2" } },
        { "type": "text", "text": "<p>HTML-Text</p>" }
      ]
    }
  ]
}
```

`page_id` muss eine existierende, nicht gelöschte `tl_page.id` sein. Jeder
Eintrag in `articles` erzeugt eine Zeile in `tl_article`, jeder Eintrag in
dessen `content`-Array eine Zeile in `tl_content` mit `ptable = tl_article`.

## Artikel-Felder

| Feld | Pflicht | Verhalten |
|---|---|---|
| `title` | ja | — |
| `alias` | nein | Ohne Angabe wird der Alias aus `title` erzeugt (Contaos `contao.slug`-Service, seitenbezogene Umlaut-Regeln). Kollisionen werden automatisch mit `-2`, `-3`, … aufgelöst — sowohl bei eigenem als auch bei automatisch erzeugtem Alias. |
| `inColumn` | nein | Default `main`. |
| `author` | nein | Default: der Backend-User, der den Import ausführt (`tl_user.id`). Nur überschreiben, wenn eine bestimmte `tl_user.id` gebraucht wird — auf Prod ist die ID unbekannt, ohne Vorgabe lieber weglassen. |
| `published` | nein | Default `true`. |
| `content` | nein | Array von Inhaltselementen (siehe unten). Ohne dieses Feld entsteht ein leerer Artikel. |
| beliebiges anderes `tl_article`-Feld | nein | Wird direkt übernommen (z. B. `cssID`, `customTpl`, `showTeaser`, `teaserCssID`, `printable`, `protected`). Array-Werte werden serialisiert (siehe unten). |

`id`, `pid`, `sorting`, `tstamp` werden ignoriert, falls mitgeschickt — sie
werden vom Importer berechnet.

## Inhaltselement-Felder

| Feld | Pflicht | Verhalten |
|---|---|---|
| `type` | ja | Muss ein registrierter Inhaltselement-Typ sein (Palettenschlüssel in `tl_content`, z. B. `text`, `headline`, `image`, `element_group`, `business_pricing`, …). |
| `children` | nein | Array weiterer Inhaltselemente. Erzeugt verschachtelte `tl_content`-Zeilen mit `pid` = ID dieses Elements und `ptable = tl_content` — für `element_group`, `accordion`, `content_slider` usw. Beliebig tief schachtelbar. |
| beliebiges andere `tl_content`-Feld | je nach Typ | Direkt als Spalte übernommen, z. B. `text`, `headline`, `cssID`, `customTpl`, `singleSRC`, `multiSRC`, `size`, `listtype`, `listitems`, projekteigene Felder wie `teamMembers`, `pricingPlans`, `businessLabel`. |

`id`, `pid`, `ptable`, `sorting`, `tstamp` werden ignoriert, falls
mitgeschickt.

## Werttransformation

- **Skalar** (String, Zahl) → wird unverändert in die Spalte geschrieben.
- **Bool** → `true`/`false` wird zu `'1'`/`''` (Contao-Checkbox-Konvention).
- **Array/Objekt** → wird rekursiv aufgelöst (siehe Dateireferenzen unten)
  und dann als PHP-`serialize()`-String gespeichert — genau das Format, das
  Contao selbst für `headline`, `cssID`, `size`, `listitems`,
  `multiColumnWizard`-Felder usw. benutzt.

### Dateireferenzen (Bilder, Downloads)

Contao speichert Dateireferenzen als binäre UUID (`BINARY(16)`), nicht als
Klartext-String. Damit das JSON trotzdem lesbar bleibt, gibt es eine feste
Markierung:

```json
{ "__file__": "3cdcbd5f-10d6-11ed-9b7a-0cc47a045e1a" }
```

Bindestriche sind optional (Contao entfernt sie vor der Konvertierung). Diese
Markierung funktioniert überall dort, wo sonst ein Wert stünde:

- als alleiniger Wert eines Feldes (`singleSRC`):
  ```json
  "singleSRC": { "__file__": "3cdcbd5f10d611ed9b7a0cc47a045e1a" }
  ```
- als Eintrag in einer Liste (`multiSRC`):
  ```json
  "multiSRC": [
    { "__file__": "3cdcbd5f10d611ed9b7a0cc47a045e1a" },
    { "__file__": "28fb941410e311ed993eef0f66fec75d" }
  ]
  ```
- verschachtelt in einer `multiColumnWizard`-Zeile (z. B. `teamMembers`):
  ```json
  "teamMembers": [
    { "image": { "__file__": "3cdcbd5f10d611ed9b7a0cc47a045e1a" }, "name": "Max Mustermann" }
  ]
  ```

Die UUID muss der Nutzer liefern (kein Dateisystemzugriff auf die
Zielumgebung). Ohne bekannte UUID das Feld weglassen und im Backend
nachtragen lassen, statt zu raten.

### `customTpl` (Varianten)

Twig-Varianten werden mit vollem Pfad ohne Endung gespeichert, nicht nur mit
dem Variantennamen:

```json
"customTpl": "content_element/text/highlight"
```

Muster: `content_element/<basiselement>/<variante>` — siehe die
Twig-Auflösung in AGENTS.md und existierende Werte in der jeweiligen
`templates/content_element/<typ>/`-Ordnerstruktur.

## Vollständiges Beispiel (Text, Bild, verschachtelte Gruppe)

```json
{
  "page_id": 185,
  "articles": [
    {
      "title": "Vereinsgeschichte",
      "content": [
        { "type": "headline", "headline": { "value": "Unsere Geschichte", "unit": "h2" } },
        {
          "type": "text",
          "text": "<p>Die Bietigheim Steelers wurden ... gegründet.</p>",
          "cssID": ["", "text-lg"]
        },
        {
          "type": "image",
          "singleSRC": { "__file__": "3cdcbd5f10d611ed9b7a0cc47a045e1a" },
          "size": ["800", "450", "crop"],
          "fullsize": false
        },
        {
          "type": "element_group",
          "children": [
            { "type": "text", "headline": { "value": "1985", "unit": "h3" }, "text": "<p>Gründung des Vereins.</p>" },
            { "type": "text", "headline": { "value": "2010", "unit": "h3" }, "text": "<p>Aufstieg in die Oberliga.</p>" }
          ]
        }
      ]
    }
  ]
}
```

## Fehlerausgabe

Der Import validiert **alles** vor dem ersten Schreibzugriff und listet alle
gefundenen Probleme auf einmal auf (z. B. `Artikel 1, Element 3: Feld 'foo'
existiert nicht in tl_content.`). Erst wenn keine Fehler gefunden wurden,
startet die Transaktion. Ein abgelehnter Import hat also nie Datenmüll
hinterlassen — das JSON kann korrigiert und erneut hochgeladen werden.
