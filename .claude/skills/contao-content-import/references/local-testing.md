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

$json = json_encode([/* Array von Inhaltselementen, siehe json-schema.md */]);

$result = (new ContentImporter())->import($json, 224); // 224 = tl_article.id
print_r($result);
```

Ausführen: `docker compose exec -u www-data web php <dateiname>.php`

## Testartikel

Lokal existiert Artikel `id=224` (auf der unveröffentlichten Testseite
`id=185`) — dafür gedacht, ohne Rücksicht Testelemente anzulegen. Für einen
Dateireferenz-Test liefert diese Abfrage eine echte UUID:

```bash
docker compose exec db mariadb -u steelers -psteelers steelers \
  -e "SELECT HEX(uuid) FROM tl_files WHERE type='file' LIMIT 1;"
```

## Nach dem Test aufräumen

Angelegte Wurzel-Elemente stehen in der `import()`-Rückgabe (`id` je
Eintrag). Erst deren Kinder löschen, dann die Wurzel-Elemente selbst:

```sql
DELETE FROM tl_content WHERE pid IN (<rootId1>, <rootId2>, ...) AND ptable = 'tl_content';
DELETE FROM tl_content WHERE id IN (<rootId1>, <rootId2>, ...);
```

Per `docker compose exec db mariadb -u steelers -psteelers steelers -e "..."`
ausführen — oder direkt im Testskript per `Database`, siehe Beispiel in
diesem Ordner. Test-PHP-Datei danach vom Host löschen (`rm <dateiname>.php`).

## Was das nicht prüft

Dieser Weg umgeht Login, Berechtigungsprüfung (`isAdmin`), CSRF und das
Template `be_content_import.html5` — er testet nur `ContentImporter::import()`
selbst, nicht `App\Controller\Backend\ContentImportAction` (den Button-Handler).
Für einen echten Ende-zu-Ende-Test bleibt ein Login im Backend nötig:
Artikelliste der Seite öffnen (`contao?do=article&id=<Seite>`) → beim
Artikel auf das Content-Import-Icon klicken.

Eine authentifizierte Session lässt sich im Bootstrap-Skript simulieren
(z. B. um die `isAdmin`-Prüfung zu testen), aber `ContentImportAction::run()`
direkt aufzurufen scheitert am CSRF-Token-Speicher (`MemoryTokenStorage`),
der nur beim echten Durchlauf durch den vollen HTTP-Kernel initialisiert
wird — kein Bug im Importer, nur eine Grenze dieses Testwegs:

```php
use Contao\BackendUser;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

$admin = BackendUser::loadUserByIdentifier('dsander'); // admin=1 in tl_user
$kernel->getContainer()->get('security.token_storage')->setToken(
    new UsernamePasswordToken($admin, 'contao_backend', $admin->getRoles())
);
```
