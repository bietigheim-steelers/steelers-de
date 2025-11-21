<?php

namespace Deployer;

require 'recipe/contao.php';
require 'contrib/cachetool.php';

// Config
set('repository', 'git@github.com:bietigheim-steelers/steelers-de.git');
set('git_tty', true);

set('shared_dirs', [
  'assets/images',
  'contao-manager',
  'files/steelers',
  '{{public_path}}/share',
  'system/config',
  'var/backups',
  'var/logs',
]);

desc('Upload project files');
task('deploy:update_code', function () {
  foreach (
    [
      'config',
      'files/steelers',
      'files/js',
      'files/css',
      'templates',
      'src',
      'composer.json',
      'composer.lock',
    ] as $src
  ) {
    upload($src, '{{release_path}}/', ['progress_bar' => false, 'options' => ['--recursive', '--relative']]);
  }
});

add('shared_files', ['config/config.yml']);

set('bin/php', function () {
  return '/usr/bin/php7.4';
});
set('bin/composer', function () {
  return '{{bin/php}} ~/bin/composer.phar';
});

set('bin/cachetool', '~/bin/cachetool.phar');

// Hosts
host('steelers.de')
  ->setLabels(['stage' => 'prod'])
  ->set('hostname', 'web01.steelers.de')
  ->set('remote_user', 'scsteelers_deployer_website')
  ->set('deploy_path', '~/web/2022-steelers-de')
  ->set('cachetool_args', '--web=SymfonyHttpClient --web-path=./{{public_path}} --web-url=https://steelers.de');

host('dev.steelers.de')
  ->setLabels(['stage' => 'dev'])
  ->set('hostname', 'web01.steelers.de')
  ->set('remote_user', 'scsteelers_deployer_dev')
  ->set('deploy_path', '~/web/dev-steelers-de');

// Hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:success', 'cachetool:clear:opcache');
