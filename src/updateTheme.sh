cd ..
vendor/bin/contao-console contao:maintenance-mode enable
composer dump-autoload
composer install -o
rm -rf var/cache/prod
vendor/bin/contao-console contao:symlinks
vendor/bin/contao-console contao:migrate --no-interaction
vendor/bin/contao-console cache:clear --no-warmup
vendor/bin/contao-console cache:warmup
vendor/bin/contao-console contao:maintenance-mode disable
