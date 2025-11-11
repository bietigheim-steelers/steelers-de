<?php

namespace Deployer;

require 'recipe/contao.php';

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

add('shared_files', ['config/config.yml']);

set('bin/php', function () {
  return '/usr/bin/php8.4';
});
set('bin/composer', function () {
  return '{{bin/php}} ~/bin/composer.phar';
});
set('nvm', 'source ~/.nvm/nvm.sh');
set('use_nvm', function () {
  return '{{nvm}} && nvm use 16 && node --version';
});

task('build', function () {
  cd('{{release_path}}');
  run('{{use_nvm}} && npm ci && npm run build');
});

// Hosts
host('steelers.de')
  ->setLabels(['stage' => 'prod'])
  ->set('hostname', 'web01.steelers.de')
  ->set('remote_user', 'scsteelers_deployer_website')
  ->set('deploy_path', '~/web/2022-steelers-de')
  ->set('branch', 'main');

host('dev.steelers.de')
  ->setLabels(['stage' => 'dev'])
  ->set('hostname', 'web01.steelers.de')
  ->set('remote_user', 'scsteelers_deployer_dev')
  ->set('deploy_path', '~/web/dev-steelers-de')
  ->set('branch', function () {
    return get('branch_override', get('default_branch', 'main'));
  });

// Hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:publish', 'deploy:cleanup');
after('deploy:update_code', 'build');
