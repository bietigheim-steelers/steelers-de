# ContentImporter lokal testen (ohne Backend-Login)

`ContentImporter` lässt sich direkt aufrufen, ohne über das Backend-Formular
zu gehen — nützlich, um ein neu geschriebenes JSON vorab zu prüfen, bevor der
Nutzer es im Backend hochlädt. Voraussetzung: die lokale Docker-Umgebung
läuft (`docker compose ps` zeigt `web` und `db` als „Up").

**Achtung Volumes:** `var/`, `vendor/`, `public/`, `system/` und `assets/`
sind eigene Docker-Volumes, keine Bind-Mounts — eine Datei, die dort auf dem
Host abgelegt wird, ist im Container **nicht** sichtbar. Das Testskript
gehört deshalb ins Projekt-Wurzelverzeichnis (oder einen anderen
bind-gemounteten Pfad) und muss danach wieder gelöscht werden.

## Bootstrap-Vorlage

```php
<?php
// Projekt-Root, temporär — vor Abschluss löschen.
declare(strict_types=1);

use App\Utils\ContentImporter;
use Contao\ManagerBundle\HttpKernel\ContaoKernel;

require __DIR__ . '/vendor/autoload.php';

ContaoKernel::setProjectDir(__DIR__);
$kernel = new ContaoKernel('prod', false);
$kernel->boot();
$kernel->getContainer()->get('contao.framework')->initialize();

$json = json_encode([
    'page_id' => 185, // vorhandene, unveröffentlichte Testseite
    'articles' => [/* ... */],
]);

$result = (new ContentImporter())->import($json, 1); // 1 = tl_user.id
print_r($result);
```

Ausführen: `docker compose exec -u www-data web php <dateiname>.php`

## Testseite

Lokal existiert Seite `id=185` ("test", `hide=0`, `published=0`) — dafür
gedacht, ohne Rücksicht Testartikel anzulegen. Für einen Dateireferenz-Test
liefert diese Abfrage eine echte UUID:

```bash
docker compose exec db mariadb -u steelers -psteelers steelers \
  -e "SELECT HEX(uuid) FROM tl_files WHERE type='file' LIMIT 1;"
```

## Nach dem Test aufräumen

```sql
-- Erst die Kinder, dann die Wurzel-Elemente, dann den Artikel.
DELETE FROM tl_content WHERE pid IN (
  SELECT id FROM tl_content WHERE pid = <articleId> AND ptable = 'tl_article'
) AND ptable = 'tl_content';
DELETE FROM tl_content WHERE pid = <articleId> AND ptable = 'tl_article';
DELETE FROM tl_article WHERE id = <articleId>;
```

Per `docker compose exec db mariadb -u steelers -psteelers steelers -e "..."`
ausführen. Test-PHP-Datei danach vom Host löschen (`rm <dateiname>.php`).

## Was das nicht prüft

Dieser Weg umgeht Login, Berechtigungsprüfung (`isAdmin`), CSRF und das
Template `be_content_import.html5` — er testet nur `ContentImporter::import()`
selbst. Für einen echten Ende-zu-Ende-Test bleibt ein Login im Backend unter
*Inhalte → Content-Import* nötig.
