<?php

namespace Deployer;

require 'recipe/contao.php';

// Config
set('repository', 'git@github.com:bietigheim-steelers/steelers-de.git');
set('git_tty', true);

add('shared_files', ['config/config.yml']);

set('bin/php', function () {
  return '/usr/bin/php7.4';
});
set('bin/composer', function () {
  return '{{bin/php}} ~/bin/composer.phar';
});

task('build', function () {
  cd('{{release_path}}');
  run('~/.nvm/versions/node/v16.20.2/bin/npm ci');
  run('~/.nvm/versions/node/v16.20.2/bin/npm run build');
});

// Hosts
host('steelers.de')
  ->set('hostname', 'web01.steelers.de')
  ->set('remote_user', 'scsteelers_deployer_website')
  ->set('deploy_path', '~/web/2022-steelers-de');

// Hooks
after('deploy:failed', 'deploy:unlock');
after('deploy:publish', 'deploy:cleanup');
after('deploy:update_code', 'build');
