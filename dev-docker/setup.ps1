<#
  Local dev setup. Run from the repository root:

    .\docker\setup.ps1                      # full setup
    .\docker\setup.ps1 -DumpFile dump.sql   # pick a specific dump
    .\docker\setup.ps1 -SkipDb              # leave the database untouched

  - starts the containers if they aren't already running
  - composer install (as www-data, so Apache can read/write what it creates)
  - drops and reimports the schema from a dump in docker/db/dumps/
  - disables forced HTTPS and clears the cache
#>
param(
    [string]$DumpFile = "",
    [switch]$SkipDb
)

Write-Host "==> starting containers"
docker compose up -d
if ($LASTEXITCODE -ne 0) { throw "docker compose up failed" }

Write-Host "==> composer install"
docker compose exec -T -u www-data web composer install --no-interaction
if ($LASTEXITCODE -ne 0) { throw "composer install failed" }

if (-not $SkipDb) {
    if ($DumpFile -eq "") {
        $sqlFiles = Get-ChildItem -Path "dev-docker\db\dumps" -Filter *.sql -File
        if ($sqlFiles.Count -eq 0) {
            Write-Host "==> no .sql file in dev-docker\db\dumps, skipping DB import"
            $SkipDb = $true
        }
        elseif ($sqlFiles.Count -gt 1) {
            throw "Multiple .sql files found in docker\db\dumps - pass -DumpFile <name>.sql to pick one."
        }
        else {
            $DumpFile = $sqlFiles[0].Name
        }
    }
}

if (-not $SkipDb) {
    # The dump has no DROP TABLE statements, so reimporting onto an existing
    # schema fails. Recreate the schema first to keep this repeatable.
    Write-Host "==> resetting schema"
    docker compose exec -T db mariadb -u root -proot -e @"
DROP DATABASE IF EXISTS steelers;
CREATE DATABASE steelers CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON steelers.* TO 'steelers'@'%';
FLUSH PRIVILEGES;
"@
    if ($LASTEXITCODE -ne 0) { throw "schema reset failed" }

    Write-Host "==> importing $DumpFile (this takes a while for large dumps)"
    docker compose exec -T db mariadb -u steelers -psteelers steelers -e "SOURCE /dumps/$DumpFile;"
    if ($LASTEXITCODE -ne 0) { throw "DB import failed" }

    Write-Host "==> disabling forced HTTPS (production dump forces it via tl_page.useSSL)"
    docker compose exec -T db mariadb -u steelers -psteelers steelers -e "UPDATE tl_page SET useSSL = 0 WHERE type = 'root';"
    if ($LASTEXITCODE -ne 0) { throw "failed to disable useSSL" }
}

# config/parameters.yml is not tracked as a cache resource, so edits to it
# don't invalidate the compiled container on their own.
Write-Host "==> clearing cache"
docker compose exec -T -u www-data web vendor/bin/contao-console cache:clear --env=prod
if ($LASTEXITCODE -ne 0) { throw "cache clear failed" }

Write-Host "==> done - http://localhost:5388"
