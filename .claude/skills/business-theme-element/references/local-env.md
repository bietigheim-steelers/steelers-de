# Lokale Docker-Umgebung

Die vier Landminen aus der Skill im Detail, jeweils mit Rettungsweg. Alle vier sind in
dieser Umgebung schon einmal zugeschlagen.

## 1. Console-Befehle als `www-data`

`docker compose exec` läuft als root. Ein `cache:clear` als root legt `var/cache/prod` mit
root-Rechten an — Apache läuft aber als `www-data` und kann dann nicht mehr hineinschreiben.
Ergebnis: **die gesamte Seite**, alle Hostnamen, antwortet mit `HTTP 500` bei
`Content-Length: 0` und **ohne Eintrag in `var/logs/`**. Das sieht aus wie ein Fehler im
gerade geschriebenen Template und ist keiner.

Also immer:

```bash
docker compose exec -T -u www-data web php vendor/bin/contao-console cache:clear
```

Wenn es doch passiert ist:

```bash
docker compose exec -T web sh -c "chown -R www-data:www-data var/cache var/logs"
docker compose exec -T -u www-data web php vendor/bin/contao-console cache:warmup
```

Erkennungsmerkmal: `docker compose exec -T web sh -c "ls -ld var/cache/prod"` zeigt `root root`.

## 2. `contao:migrate` nicht durchlaufen lassen

Die lokale Datenbank ist ein Produktions-Dump und enthält Spalten längst entfernter
Extensions (Gallery Creator, con4gis, Newsletter, mp_forms …). `contao:migrate` will die
alle löschen — quer über `tl_content`, `tl_module`, `tl_news`, `tl_page`, `tl_user` und
weitere. Ob das gewollt ist, entscheidet nicht der Agent.

Stattdessen die nötige Anweisung aus dem Trockenlauf herausziehen:

```bash
docker compose exec -T -u www-data web php vendor/bin/contao-console contao:migrate --no-interaction --dry-run 2>&1 \
  | grep -oE "ALTER TABLE tl_content ADD [^;]*"
```

und nur den `ADD`-Teil ausführen:

```bash
docker compose exec -T db mariadb -u steelers -psteelers steelers \
  -e "ALTER TABLE tl_content ADD COLUMN businessLabel VARCHAR(64) DEFAULT '' NOT NULL;"
```

Vor dem Trockenlauf einmal den Cache leeren, sonst kennt Contao die neuen DCA-Felder noch
nicht und der Lauf zeigt sie gar nicht erst an.

Die ausgeführten `ALTER TABLE`-Anweisungen dem Nutzer nennen — dev und prod brauchen sie
ebenfalls.

## 3. `contao:filesync` nur mit Pfad

Die meisten Dateien unter `files/steelers/` existieren lokal nicht (sie werden nicht
mitversioniert). Ein voller `contao:filesync` schließt daraus, dass die Dateien gelöscht
wurden, und entfernt **~3000 Zeilen aus `tl_files`**. Damit zeigen alle UUID-Referenzen ins
Leere: Partner-Logos, News-Bilder, Downloads, Bild-Inhaltselemente.

Immer nur den betroffenen Teilbaum:

```bash
docker compose exec -T -u www-data web php vendor/bin/contao-console contao:filesync files/business
```

Wenn es doch passiert ist, `tl_files` aus dem Dump wiederherstellen. Der Dump liegt unter
`dev-docker/db/dumps/` und ist im DB-Container als `/dumps/` eingehängt:

```bash
# Zeilenbereich des tl_files-Blocks bestimmen
docker compose exec -T db sh -c "grep -n 'tl_files' /dumps/<dump>.sql | head -5"
docker compose exec -T db sh -c "awk 'NR>{DATENZEILE} && /^-- Tabellenstruktur/ {print NR; exit}' /dumps/<dump>.sql"

# nur den Datenblock (ohne CREATE TABLE) herausschneiden und einspielen
docker compose exec -T db sh -c "sed -n '{VON},{BIS}p' /dumps/<dump>.sql > /tmp/tl_files.sql"
docker compose exec -T db mariadb -u steelers -psteelers steelers -e 'TRUNCATE TABLE tl_files;'
docker compose exec -T db sh -c "mariadb -u steelers -psteelers steelers < /tmp/tl_files.sql"
```

Nur die `INSERT`-Zeilen einspielen, nicht das `CREATE TABLE` — dann bleiben Indizes und
`AUTO_INCREMENT` der aktuellen Tabelle erhalten.

Danach prüfen, ob die Referenzen wieder aufgehen:

```sql
SELECT COUNT(*) AS partner, SUM(f.id IS NOT NULL) AS logos_ok
FROM tl_tilastot_partners p LEFT JOIN tl_files f ON f.uuid = p.logo WHERE p.published = 1;
```

Anschließend die eigenen neuen Dateien mit einem **gescopten** Lauf nachtragen.

## 4. Der Setup-Script setzt die Datenbank zurück

Startet der Nutzer die Umgebung neu auf, spielt der Setup-Script den Dump aus
`dev-docker/db/dumps/` erneut ein. Danach sind **alle** DB-seitigen Ergebnisse weg: eigene
Spalten, Demo-Seite, Inhaltselemente, Module, FAQ-Kategorien. Auch Dateien unter `files/`,
die nicht im Repo liegen (Demo-Bilder), können dabei verschwinden.

Der Code auf der Platte bleibt unberührt. Wiederherstellen in dieser Reihenfolge:

```bash
docker compose exec -T -u www-data web php vendor/bin/contao-console cache:clear
docker compose exec -T -u www-data web php vendor/bin/contao-console contao:migrate --no-interaction --dry-run 2>&1 \
  | grep -oE "ALTER TABLE (tl_content|tl_module) ADD [^,]*(, ADD [^,]*)*"
# nur die ADD-Teile ausfuehren, dann Demo-Bilder zurueckkopieren:
docker compose exec -T -u www-data web php vendor/bin/contao-console contao:filesync files/business
# zuletzt die Seed-Skripte
```

Deshalb die Seed-Skripte aufbewahren (siehe `verification.md`) — und vor einem erneuten Lauf
prüfen, ob Teile schon existieren. Ein Skript, das mittendrin abbricht (etwa wegen eines
inzwischen umbenannten Feldes), hinterlässt einen halben Seitenbaum; der nächste Lauf legt
dann Dubletten an.

## 5. `mariadb` statt `mysql`

Im `db`-Container gibt es keinen `mysql`-Client:

```bash
docker compose exec -T db mariadb -u steelers -psteelers steelers -e "SELECT 1;"
```

Die Warnung „Using a password on the command line interface can be insecure" landet auf
stderr; bei Bedarf mit `| grep -v insecure` ausblenden. Achtung: liefert `grep` dann keine
Zeile mehr, ist der Exit-Code 1, obwohl nichts schiefging.

## Kurzreferenz

| | |
|---|---|
| Frontend | `http://localhost:5388`, Business-Site über `-H "Host: business.localhost"` |
| Business-Theme | `tl_theme.id = 5`, Template-Ordner `templates/business` |
| Business-Root-Seite | `tl_page` mit `dns = business.localhost` |
| Contao-Log | `var/logs/prod-<datum>.log` (im Container) |
| PHP-Syntaxcheck | `docker compose exec -T web php -l <datei>` |
| Twig-Syntaxcheck | `… contao-console lint:twig <dateien>` |
| Element registriert? | `… contao-console debug:container --tag=contao.content_element` |
| Modul registriert? | `… contao-console debug:container --tag=contao.frontend_module` |

## Nicht verwechseln

Diese Symptome sehen nach Fehler aus, sind aber lokale Datenlücken:

- `<img src alt>` ohne Quelle → die Datei fehlt lokal. Das bestehende Partnerverzeichnis
  verhält sich identisch; auf prod ist es in Ordnung.
- 404 auf `/files/css/steelers.css` → der Steelers-Build wird lokal nicht erzeugt und ist
  gitignored. Betrifft die Business-Site nicht, die lädt `files/business/css/style.css`.
