# Steelers-App ↔ Contao-API: Zusammenspiel

Dieses Dokument beschreibt, wie die mobile App (iOS + Android, Flutter) mit der Contao-Installation von
steelers.de zusammenarbeitet. Es ist so geschrieben, dass man die App **allein damit und mit
[`openapi.yaml`](openapi.yaml)** umsetzen kann. Beide Dateien gehören zusammen in das App-Projekt
(z. B. nach `docs/api/`).

- **`openapi.yaml`** ist der maschinenlesbare Vertrag (Pfade, Felder, Typen).
- **Dieses Dokument** erklärt das Verhalten dahinter: was die App wann lädt, wie sie es darstellt, was
  sie ignorieren muss und welche Eigenheiten die Daten haben.

> Bei Widersprüchen gilt: Verhalten der laufenden API > `openapi.yaml` > dieses Dokument. Alles lässt
> sich mit `curl` nachprüfen (siehe „Ausprobieren“).

**Stand der API (bei Erstellung dieses Dokuments)**

| Teil | Stand |
|---|---|
| Daten-Endpunkte `/news /games /standings /players /partners` | fertig, lokal getestet, in `main` |
| App-Aufbau `/app/config`, `/app/screens/{key}` und Backend-Bereich „App“ | fertig und lokal getestet, **noch nicht ausgerollt** (braucht auf dem Server die Migration `contao:migrate --migrations-only` und danach Pflege im Backend) |
| Push (`/devices`, FCM-Versand) | **noch nicht gebaut** (nur Spezifikation) |

Solange `/app/*` auf dem Zielserver fehlt, antwortet es mit `404`; die App fällt dann auf ihre eingebaute
Standardkonfiguration zurück (Abschnitt 3).

---

## 1. Grundidee

**Die App besitzt Design und Verhalten, Contao bestimmt Aufbau und Inhalt.**

- Die App hat einen **festen Satz an Bausteinen** (Block-Typen) und eingebauten Funktionen. Das ist Code
  und braucht ein Release, wenn er sich ändert.
- Contao liefert eine **Konfiguration**: welche Tabs und Menüeinträge es gibt, welcher Screen zuerst
  erscheint, welche Bausteine in welcher Reihenfolge auf jedem Screen stehen, mit welchen Texten und
  Schaltern. Redakteure pflegen das im Contao-Backend unter **App**. Dafür braucht es **kein App-Release**.
- Fachliche Daten (News, Spielplan, Tabelle, Kader, Partner) liegen ebenfalls in Contao und werden über
  eigene Daten-Endpunkte geladen. Ein *Daten-Baustein* auf einem Screen sagt nur, **dass** und **wie** so
  eine Liste erscheint; die Daten holt sich die App selbst.
- Inhalte der Webseite (Artikel, einzelne Inhaltselemente) kann ein Redakteur in einen Screen einbinden. Der
  **Server** wandelt sie in einfache Bausteine um; die App sieht nie HTML-Seiten der Webseite.

```
Contao-Backend "App"                       App (Flutter)
  Screens  (Tabs, Menü, Reihenfolge) ──►  GET /app/config           → Navigation, Startscreen, Schalter
  Bausteine je Screen                ──►  GET /app/screens/{key}    → Liste von Blöcken
  Schalter (Feature-Flags)           ──►  (in /app/config → flags)
  News, Spiele, Tabelle, Kader,      ──►  GET /news /games /standings /players /partners
  Partner (bestehende Pflege)
```

**Grundregeln, auf die sich alles stützt**

1. **Unbekanntes ignorieren.** Unbekannte Block-Typen, unbekannte Felder, unbekannte Icon-Schlüssel,
   unbekannte Schalter: nie abstürzen, still überspringen. Nur so können neue Bausteine ohne Zwang zum
   Update ausgeliefert werden (der Redakteur sperrt sie per `minAppVersion` für alte Apps).
2. **Immer etwas anzeigen.** Die App liefert eine eingebaute Standardkonfiguration mit und merkt sich die
   zuletzt geladene. Ohne Netz oder bei Serverfehlern erscheint der letzte bekannte Stand.
3. **Der Server filtert, nicht die App.** Veröffentlichung, Zeitfenster, Plattform und Mindestversion
   prüft der Server anhand von `platform` und `appVersion`. Die App zeigt, was geliefert wird.

---

## 2. Server und Umgebungen

| Umgebung | Basis-URL | Hinweis |
|---|---|---|
| Produktion | `https://www.steelers.de/api/v1` | echte Inhalte; `/app/*` erst nach Ausrollen und Pflege im Backend |
| Entwicklung | `https://dev.steelers.de/api/v1` | zum Testen, sobald die API dort ausgerollt ist |
| Lokal (Docker) | `http://localhost:5388/api/v1` | nur auf dem Entwicklerrechner; Bilddateien fehlen dort |

Die Basis-URL muss in der App **konfigurierbar** sein (Build-Flavor oder verstecktes Debug-Menü), damit
Testversionen auf `dev` zeigen.

### Allgemeines Verhalten

- **Nur `GET`** (außer später `POST/DELETE /devices` für Push, siehe Abschnitt 8).
- **JSON, UTF-8.** Erfolg: `{ "data": …, "meta": … }` (`meta` nur bei Listen mit Paginierung).
  Fehler: `{ "error": { "code": "not_found", "message": "…" } }` mit passendem HTTP-Status.
- **Keine Anmeldung.** Alle Inhalte sind öffentlich. In der Spec steht ein optionaler Header `X-App-Key`;
  er wird derzeit **nicht geprüft**. Den HTTP-Client so bauen, dass sich später ein fester Header
  ergänzen lässt.
- **Caching:** Jede Antwort hat `Cache-Control: public, max-age=…` und einen `ETag`. Die App sendet bei
  wiederholten Abrufen `If-None-Match` und behandelt `304 Not Modified` als „unverändert, gespeicherte
  Kopie weiterverwenden“. Richtwerte der Server: Konfiguration/Screens/Spiele 60 s, News/Tabelle 120 s,
  Kader/Partner 600 s. Nicht häufiger abrufen als diese Werte.
- **Zeiten:** ISO 8601 mit Offset, z. B. `2026-10-30T19:30:00+01:00`. In der App in der Zeitzone
  `Europe/Berlin` anzeigen (nicht in der Gerätezone, damit ein Spiel überall gleich beginnt).
- **Bilder:** immer **absolute** URLs, bereits auf App-Größe verkleinert (News 750 px, Inhaltsbilder
  1000 px, Spielerfotos 800 px, Partnerlogos 400 px, Teamlogos 200 px Breite). `image`/`logo` kann
  `null` sein → Platzhalter. Mit Bild-Cache laden (`cached_network_image`).
- **Fehler und Ausfälle:** Netzwerkfehler und 5xx → auf gespeicherte Kopie zurückfallen und still
  nachladen. `404` bei einem Screen heißt „gibt es nicht (mehr) oder ist für diese App-Version nicht
  freigegeben“ → Eintrag aus der Navigation nehmen bzw. Hinweis zeigen, nicht abstürzen.

---

## 3. Start der App und Konfiguration

### Ablauf

1. **Sofort starten** mit der zuletzt gespeicherten Konfiguration (oder, beim allerersten Start ohne
   Netz, mit der eingebauten Standardkonfiguration, siehe unten).
2. Im Hintergrund `GET /app/config?platform={ios|android}&appVersion={x.y.z}` laden (mit
   `If-None-Match`). Bei `200` Navigation aktualisieren und speichern; bei `304` nichts tun.
3. Beim Zurückkehren in die App (Resume) erneut abrufen, aber höchstens alle 60 s.
4. Einen Screen (`GET /app/screens/{key}?platform=…&appVersion=…`) laden, **wenn er geöffnet wird**. Die
   zuletzt geladene Kopie sofort zeigen und im Hintergrund aktualisieren („stale-while-revalidate“).
   Pro Screen-Schlüssel separat speichern, samt ETag.

`platform` und `appVersion` sind bei **jedem** Abruf unter `/app/*` Pflicht (aus dem Paket lesen, z. B. mit
`package_info_plus`). Fehlen sie, filtert der Server nicht – das ist nur zum Testen gedacht.

### `GET /app/config`

```json
{
  "data": {
    "revision": 1793793587,
    "home": "start",
    "tabs": [
      { "key": "start", "title": "Start", "icon": "home", "type": "screen", "url": null },
      { "key": "news",  "title": "News",  "icon": "news", "type": "screen", "url": null }
    ],
    "menu": [
      { "key": "info",     "title": "Info & Anfahrt", "icon": "info",     "type": "screen",   "url": null },
      { "key": "settings", "title": "Einstellungen",  "icon": "settings", "type": "native",   "url": null },
      { "key": "shop",     "title": "Fanshop",        "icon": "cart",     "type": "link",     "url": "https://www.steelers.de" }
    ],
    "flags": { "ticketShop": true, "maxItems": 5, "welcomeText": "Hallo Steelers-Fans" }
  }
}
```

- **`tabs`** – untere Navigationsleiste in der gelieferten Reihenfolge. Empfehlung: höchstens 5 Einträge;
  zeigt der Server mehr, die ersten 4 als Tabs und den Rest unter „Mehr“.
- **`menu`** – weitere Einträge (Bereich „Mehr“ bzw. Seitenmenü), ebenfalls in Reihenfolge.
- **`home`** – Schlüssel des Screens, der beim Start erscheint. Ist es kein Tab, trotzdem öffnen.
- **`type` eines Eintrags**
  - `screen` → Inhalt kommt von `GET /app/screens/{key}`.
  - `native` → fest in der App eingebaute Funktion (z. B. `settings`). Die App hat eine kleine Tabelle
    „bekannte native Schlüssel → Seite“. **Unbekannter nativer Schlüssel → Eintrag nicht anzeigen.**
  - `link` → externe Seite `url` im In-App-Browser (`url_launcher`/Custom Tab) öffnen.
- **`icon`** – Schlüssel aus dem Icon-Satz der App (Zuordnung `String → IconData`). Unbekannt oder `null` →
  Standard-Icon bzw. gar keins. Die Liste der Schlüssel legt das App-Projekt fest und sollte dem Redakteur
  mitgeteilt werden (z. B. in dieser Datei ergänzen).
- **`revision`** – größter Änderungszeitpunkt der Konfiguration. Zum schnellen Vergleich nutzbar; verlässlich
  für „hat sich etwas geändert?“ ist der ETag.
- **`flags`** – Schalter. Werte sind je nach Eingabe `bool`, `int`, `double` oder `String`. Der Server
  liefert nur **veröffentlichte** und für diese App sichtbare Schalter. **Ein fehlender Schalter heißt
  „Standardwert der App“** – die App legt für jeden verwendeten Schalter einen Standard fest und liest nie
  mit `!`.

### Eingebaute Standardkonfiguration

Das App-Paket enthält eine Datei im selben Format wie die API (`default_config.json` und je Screen eine
`default_screen_{key}.json`) mit mindestens den Tabs News, Spielplan, Tabelle, Kader und „Mehr“. Sie wird
verwendet, solange noch nie etwas vom Server geladen wurde, und als Rückfall, wenn die gespeicherte Kopie
fehlt oder unlesbar ist. So ist die App auch vor der ersten Pflege im Backend benutzbar.

---

## 4. Screens und Bausteine

### `GET /app/screens/{key}`

```json
{
  "data": {
    "key": "start",
    "title": "Start",
    "updatedAt": "2026-11-04T12:59:47+01:00",
    "blocks": [ { "type": "promo", "…": "…" }, { "type": "news_list", "…": "…" } ]
  }
}
```

Die App rendert `blocks` **in der gelieferten Reihenfolge** untereinander (scrollbare Liste). Ein Screen
ohne Bausteine ist gültig (`blocks: []`) → Leerzustand zeigen. `404` → siehe Abschnitt 2. `native` und
`link` haben keine Screens (`404`).

### Implementierung: Renderer-Registry

Ein Widget je Block-Typ, nachgeschlagen in einer Tabelle. Unbekannt → nichts rendern.

```dart
final Map<String, Widget Function(Map<String, dynamic> block)> blockRenderers = {
  'headline': (b) => HeadlineBlock(b),
  'text': (b) => TextBlock(b),
  // …
};
Widget buildBlock(Map<String, dynamic> b) =>
    blockRenderers[b['type']]?.call(b) ?? const SizedBox.shrink();
```

Modelle dürfen typisiert sein (z. B. `freezed` + `json_serializable`), müssen aber bei unbekanntem `type` oder
fehlenden/zusätzlichen Feldern **tolerant** bleiben (kein Wurf beim Parsen eines Blocks, der übrige Screen
bleibt sichtbar).

### Inhaltsbausteine (bringen ihren Inhalt mit)

| `type` | Felder | Darstellung |
|---|---|---|
| `headline` | `text`, `level` (1–6) | Überschrift; `level` 1 = größte Stufe des App-Themes |
| `text` | `html` | formatierter Text, siehe „HTML-Teilmenge“ |
| `image` | `image` (`url`, `width`, `height`, `alt`, `caption`), `linkUrl?` | Bild in voller Breite, Seitenverhältnis aus `width`/`height`; `caption` darunter; `linkUrl` macht es antippbar (Linkregeln unten) |
| `list` | `items[]`, `ordered` | Aufzählung bzw. nummerierte Liste |
| `button` | `label`, `url`, `external` | Schaltfläche; `external=true` → Browser, sonst App-intern |
| `download` | `label`, `url`, `mimeType?`, `size?` (Bytes) | Zeile mit Dateisymbol; Antippen öffnet/lädt die Datei (PDF im Systemviewer) |
| `video` | `provider` (`youtube`\|`vimeo`), `id`, `title?` | Vorschaubild mit Abspielen; YouTube-ID → `youtube_player_iframe` o. ä. oder Link zur Video-App |
| `promo` | `title`, `text?`, `image?`, `label?`, `url?` | auffälliger Banner (Bild, Titel, Text, optional Button/Antippen) |

**HTML-Teilmenge in `text.html`** (und in `Player.about`, `Partner.description`): nur
`<p> <strong> <em> <ul> <ol> <li> <br> <a href>`. Alles andere hat der Server schon entfernt. Mit
`flutter_widget_from_html_core` oder einem eigenen kleinen Renderer darstellen; Links nach den Linkregeln
öffnen. Keine WebView.

### Daten-Bausteine (enthalten nur Konfiguration)

Die App lädt die Daten selbst. Alle haben `title?` (optionale Überschrift über dem Baustein; `null` = keine).

| `type` | Konfiguration | Daten holen | Darstellung |
|---|---|---|---|
| `news_list` | `limit` (Standard 5), `category?` (Alias), `layout` (`list`\|`carousel`) | `GET /news?perPage={limit}&category={category}` | Karten mit Bild, Titel, Datum, gekürztem Teaser; Antippen → News-Detail (unten) |
| `next_game` | – | `GET /games?status=upcoming` → **erstes** Element | große Karte: Gegner, Logos, Datum/Uhrzeit, Ort, Ticketlink (`tickets`) |
| `games` | `status` (`upcoming`\|`finished`), `limit?`, `layout` (`list`\|`slider`) | `GET /games?status={status}` (bei `slider`: `GET /games`) | Spielliste; bei `finished` **absteigend** sortieren (neueste zuerst), bei `upcoming` aufsteigend; `limit` in der App anwenden. `layout=slider` (im Backend: „Welche Spiele“ = Slider; `status` ist dann `upcoming`): Startseiten-Slider mit bis zu 3 Karten – links das letzte Spiel (mit Ergebnis), Mitte das nächste Heimspiel, rechts das nächste weitere Spiel (heim oder auswärts, aber nicht das mittlere); `status`/`limit` entfallen |
| `standings` | `compact` | `GET /standings` | Tabelle; `compact` = Platz, Team, Punkte (ggf. nur Umfeld des eigenen Teams) |
| `roster` | `position?` | `GET /players?position={position}` | Spielerliste mit Foto/Nummer; Antippen → Spieler-Detail |
| `partners` | `category?`, `layout` (`grid`\|`slider`) | `GET /partners?category={category}` | Logo-Raster bzw. Slider; Antippen → Partner-Detail oder `url` |

Hinweise zu den Daten-Bausteinen:

- **Eigenes Team hervorheben:** In den Daten ist „Bietigheim Steelers“ das Team mit `shortName: "SCB"`
  (derzeit `id: 36`). Die ID soll nicht fest im Code stehen: Schalter `myTeamId` (Zahl) im Backend anlegen
  lassen und in der App lesen; Standard: Team mit `shortName == "SCB"`.
- **Detailansichten** (News, Spieler, Partner, Spiel) sind **App-Funktionen**, keine Contao-Screens. Sie
  verwenden die Detail-Endpunkte (`/news/{id}`, `/players/{id}`, `/games/{id}`) oder die bereits geladenen
  Listendaten. Partner haben keinen Detail-Endpunkt; die Liste enthält schon alles.
- Mehrere Daten-Bausteine desselben Typs auf einem Screen sind erlaubt (z. B. zwei `news_list` mit
  unterschiedlicher `category`). Anfragen dedupliziert über den HTTP-Cache (ETag) laufen lassen.
- Schlägt das Laden eines Daten-Bausteins fehl, nur diesen Baustein mit „Konnte nicht geladen werden –
  erneut versuchen“ ersetzen, nicht den ganzen Screen.

### Linkregeln (`url`, `linkUrl`)

| Form | Verhalten |
|---|---|
| `https://…` (auch `http://`) | im In-App-Browser öffnen |
| `app://screen/{key}` | zum Screen mit diesem Schlüssel navigieren, wenn er in `tabs`/`menu` oder per Direktaufruf ladbar ist (bei `404` nichts tun) |
| `mailto:…`, `tel:…` | an das System übergeben |
| sonst | ignorieren |

`app://`-Links erscheinen in `button.url`, `promo.url` und `image.linkUrl`. Später auch in Push-Nachrichten.

---

## 5. Daten-Endpunkte im Detail

Felder siehe `openapi.yaml`; hier das Verhalten und die Eigenheiten der Daten.

### News – `GET /news`, `GET /news/{id}`

- Parameter: `page` (ab 1), `perPage` (1–50, Standard 20), `category` (Alias), `archive` (kommagetrennte
  Archiv-IDs; ohne Angabe alle außer dem Videoportal), `since` (nur Änderungen nach Zeitpunkt).
- `meta`: `{ page, perPage, total }`. Weitere Seite laden, solange `page * perPage < total`.
- Neueste zuerst.
- **Es gibt keinen Volltext.** `teaser` ist Klartext (Absätze durch Leerzeilen getrennt). **Achtung:** bei
  manchen News steht im Teaser der ganze Artikel (über 2000 Zeichen). Die App kürzt Teaser in Listen
  (z. B. 3 Zeilen) und zeigt in der Detailansicht den Teaser vollständig.
- **Detailansicht:** Bild, Titel, Datum, Teaser, Kategorien und ein Button „Auf steelers.de lesen“ (`webUrl`).
  `webUrl` kann `null` sein.
- `gameId` verknüpft ein Spiel (→ `GET /games/{id}`), `video.url` gibt es nur bei Videobeiträgen.

### Spiele – `GET /games`, `GET /games/{id}`

- Parameter: `round` (Saison/Runde; **ohne Angabe die Runde des nächsten anstehenden Spiels**, sonst die des
  letzten), `status` (`upcoming|live|finished`), `team` (Team-ID), `from`, `to` (`YYYY-MM-DD`).
- Sortierung: immer **aufsteigend** nach Beginn.
- `status`: `finished` sobald der Import das Spiel beendet hat; ohne dieses Flag gilt ein Spiel bis 4 Stunden
  nach Beginn als `live`. Es gibt **keine Live-Tore** und keinen Liveticker. Während `live` kann das
  Ergebnis veraltet oder `null` sein.
- `homeScore`/`awayScore`/`resultType`/`periodScores` sind erst mit Ergebnis gesetzt. `resultType`:
  `regulation`, `overtime` (n. V.), `shootout` (n. P.), `awarded` (Spielwertung).
- `home`/`away` sind Team-Objekte (`id`, `name`, `shortName`, `city`, `logo?`). **`Team.id` ist die
  rundenübergreifende `tilastotid`**, nicht eine Datenbank-ID.
- `tickets.eventimUrl` / `magentaUrl` sind Links (Ticketkauf bzw. Streaming); jeweils einzeln `null`.
- Die Startzeit ist serverseitig bereinigt; so anzeigen, wie geliefert.

### Tabelle – `GET /standings`

- Parameter `round` wie bei Spielen. Zeilen kommen fertig nach Rang sortiert (`rank`).
- Spalten: `games`, Siege nach 60 Min. (`regulationWins`), nach Verlängerung (`overtimeWins`), im
  Penaltyschießen (`penaltyWins`), entsprechende Niederlagen (`penaltyLosses`, `overtimeLosses`,
  `regulationLosses`), `goalsFor`, `goalsAgainst`, `goalDifference`, `penalties` (Strafminuten), `points`.

### Kader – `GET /players`, `GET /players/{id}`

- Parameter `position` (z. B. `G`, `D`, `F`). Sortierung nach Trikotnummer.
- Positionswerte sind historisch gewachsen: Stürmer können `F`, `C`, `CE`, `LW`, `RW` sein, Verteidiger `D`
  oder `LD`, Torhüter `G`. Für Gruppen „Torhüter / Verteidiger / Stürmer“ so zusammenfassen; unbekannte
  Werte unter „Weitere“.
- `pictures` ist eine Liste (erstes Bild = Porträt); kann leer sein. `about` ist HTML-Teilmenge oder `null`.
  `birthday` ist `YYYY-MM-DD`, `height` in cm, `weight` in kg; fast alle Felder sind `null`-bar.

### Partner – `GET /partners`

- Parameter `category` (Schlüssel). Sortierung nach Name.
- `categories` und `branches` sind Listen aus `{key, label}`: ein Partner kann **mehrere** Kategorien haben.
  Reihenfolge der Kategorien für eine gruppierte Ansicht (wichtigste zuerst): `platin`, `premium`, `gold`,
  `silber`, `bronze`, `business`, `lounge`, `medien`, `video`, `carpool`, `team`, `supporter`,
  `nachwuchs_haupt`, `nachwuchs`, `nachwuchsspieler`.
- `logo` kann `null` sein (dann Name als Text zeigen).

---

## 6. Pflege im Backend (Sicht der Redaktion)

Damit die App-Seite die Möglichkeiten kennt: im Contao-Backend gibt es die Gruppe **App** mit

- **Screens** – Titel, Schlüssel, Art (Screen / Eingebaute Funktion / Link), Platzierung (Tab / Menü / nur
  verlinkt), Icon, Reihenfolge, „Startscreen“, Veröffentlichung, Zeitfenster, Mindest-App-Version,
  Plattform. Je Screen eine Liste von **Bausteinen** mit denselben Sichtbarkeitsfeldern.
- **Schalter** – Name/Wert-Paare, die als `flags` in `/app/config` erscheinen (`ja/nein/1/0/true/false` →
  Boolean, Zahl → Zahl, sonst Text).

Die Block-Typen dort entsprechen exakt Abschnitt 4. Zusätzlich gibt es zwei Bausteine, die in der API
nicht als eigener Typ auftauchen, weil der Server sie auflöst:

- **Artikel der Webseite einbinden** – die Inhaltselemente eines Artikels werden zu `text`, `headline`,
  `image`, `list`, `button`, `download` und `video` Blöcken. Alles andere (Galerien, Akkordeons, Formulare,
  Slider …) entfällt.
- **Inhaltselement der Webseite einbinden** – dasselbe für ein einzelnes Element.

**Regel für neue Bausteine:** Sobald die App einen neuen Block-Typ kann, trägt der Redakteur beim ersten
Baustein dieses Typs `minAppVersion` = Version, die ihn versteht. Ältere Apps bekommen ihn dann nicht.
Genau deshalb muss die App Unbekanntes trotzdem überspringen (Sicherheitsnetz).

**Einen Screen testen:** im Backend einen Baustein zeitweise unveröffentlicht lassen und mit `dev.steelers.de`
als Basis-URL in der App prüfen.

---

## 7. Versionierung und Verträge

- Pfad enthält `v1`. Innerhalb von `v1` gibt es nur **additive** Änderungen: neue Felder, neue Block-Typen,
  neue optionale Parameter. Bestehende Felder ändern weder Typ noch Bedeutung. Brechende Änderungen wären `v2`.
- Die App darf sich daher auf bestehende Felder verlassen und muss Neues ignorieren.
- Block-Felder, die laut Spec `nullable` sind, können `null` oder auch ganz fehlen.
- Zahlen können `int` oder `double` sein (`flags`); nicht auf einen Typ casten, ohne zu prüfen.

---

## 8. Push-Benachrichtigungen (geplant, noch nicht in der API)

Noch **nicht** implementiert, aber die App sollte darauf vorbereitet werden:

- **Firebase Cloud Messaging** für iOS (über APNs) und Android (`firebase_messaging`).
- Geplante Endpunkte (siehe `openapi.yaml`): `POST /devices` (FCM-Token, `platform`, `topics`, `appVersion`)
  und `DELETE /devices/{token}`. Heute antworten sie mit `404`. Die App darf Aufrufe vorbereiten, muss aber
  einen Fehlschlag verkraften.
- Geplante Topics: `news` (neue Meldung), `games` (Spielbeginn), `results` (Endergebnis). Nutzer schalten sie
  in den Einstellungen der App ein und aus; Standard: alle an, aber die Berechtigung erst bei passender
  Gelegenheit erfragen, nicht beim ersten Start.
- Nutzlast (Vorschlag, wird in Phase 3 festgelegt): `data.url` mit einem Link nach den Linkregeln
  (`app://screen/{key}` oder `https://…`) und optional `data.newsId` / `data.gameId`. Ein Tipp auf die
  Benachrichtigung öffnet das Ziel.

---

## 9. Empfohlener Aufbau der App (Vorschlag)

| Thema | Empfehlung |
|---|---|
| Struktur | Feature-Ordner (`config`, `screens`, `news`, `games`, `standings`, `roster`, `partners`, `settings`) |
| Navigation | `go_router`; Tabs aus `config.tabs`, Deep-Link-Schema `app://screen/{key}` |
| State | Riverpod (`AsyncNotifier` je Datenquelle, Konfiguration als eigener Provider) |
| HTTP | `dio` mit Interceptor für ETag/`If-None-Match` und Fallback auf Speicher; Basis-URL + `platform` + `appVersion` zentral |
| Speicher | `shared_preferences` bzw. `hive`/`isar` für Konfiguration, Screens und Listen (JSON samt ETag) |
| Bilder | `cached_network_image` |
| Text | `flutter_widget_from_html_core` nur für die HTML-Teilmenge |
| Links | `url_launcher` bzw. In-App-Browser (`flutter_custom_tabs`/`SFSafariViewController`) |
| Push | `firebase_messaging` + `flutter_local_notifications` |
| Modelle | `freezed` + `json_serializable`, tolerant gegenüber Unbekanntem |

**Reihenfolge der Umsetzung**

1. Projekt, Flavors (Basis-URL), Theme, API-Client mit ETag-Cache, Modelle (aus `openapi.yaml` ableiten).
2. Konfiguration laden, speichern, Standardkonfiguration; Tab-Navigation aus `config`.
3. Block-Renderer-Registry mit allen Inhaltsbausteinen; Screen-Seite mit Leer- und Fehlerzuständen.
4. Daten-Bausteine samt Detailansichten: News, Spiele/`next_game`, Tabelle, Kader, Partner.
5. Einstellungen (native Seite `settings`), Schalter-Abfrage, Offline-Verhalten prüfen (Flugmodus).
6. Push (nach Phase 3 der API), Store-Vorbereitung (Icons, Screenshots, Datenschutz, Konten).

---

## 10. Bekannte Eigenheiten und offene Punkte

- **Teaser-Länge** bei News (siehe Abschnitt 5) – App kürzt selbst.
- **Kein Live-Ticker.** Ergebnis-/Statuswechsel kommen mit dem Import, nicht sekundengenau.
- **Aktuelle Runde** für Spiele und Tabelle bestimmt der Server aus dem nächsten Spiel. Gibt es mehrere
  Ligen/Runden, muss die App `round` explizit setzen können (noch kein `/rounds`-Endpunkt).
- **Lokale Entwicklung:** Dort fehlen die Bilddateien, deshalb sind Bilder `null`. Die App muss mit
  fehlenden Bildern umgehen können (Platzhalter), unabhängig davon.
- **`X-App-Key`** wird noch nicht ausgewertet.
- **Native Schlüssel und Icon-Schlüssel** legt das App-Projekt fest; die Liste muss der Redaktion
  mitgeteilt werden (hier ergänzen, sobald vorhanden).
- Offen: Welche Tabs die erste Version hat; ob die Detailansicht von News mehr als Teaser braucht
  (derzeit bewusst nicht).

---

## 11. Ausprobieren

Lokal (Docker läuft auf Port 5388) oder gegen `dev.steelers.de`:

```bash
BASE=http://localhost:5388/api/v1

# Navigation und Schalter
curl "$BASE/app/config?platform=ios&appVersion=1.0.0"

# Ein Screen
curl "$BASE/app/screens/start?platform=ios&appVersion=1.0.0"

# Bedingte Anfrage (ETag aus der vorherigen Antwort) → 304
curl -i -H 'If-None-Match: "<etag>"' "$BASE/app/config?platform=ios&appVersion=1.0.0"

# Daten
curl "$BASE/news?perPage=3"
curl "$BASE/games?status=upcoming"
curl "$BASE/standings"
curl "$BASE/players?position=G"
curl "$BASE/partners?category=platin"
```

Auf dem Entwicklerrechner, auf dem die API entstanden ist, liegen Beispielinhalte (Screens `start`, `info`,
`news`, …, mit absichtlichen Fallen wie nicht veröffentlichten, abgelaufenen und versionsgesperrten
Einträgen). Wo sie fehlen, im Backend unter **App** ein paar Screens und Bausteine anlegen. Gutes Testmaterial
für die Filterlogik: `?platform=android&appVersion=1.0.0` gegenüber `?platform=ios&appVersion=2.1.0`.

Die vollständige, maschinenlesbare Beschreibung liegt in `openapi.yaml` (öffnen z. B. mit Swagger Editor
oder `npx @redocly/cli preview-docs openapi.yaml`).
