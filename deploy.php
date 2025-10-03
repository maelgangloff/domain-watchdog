<?php

namespace Deployer;

require 'recipe/symfony.php';

// Config
set('repository', 'https://github.com/maelgangloff/domain-watchdog.git');
set('branch', 'demo-instance');
set('keep_releases', 1);

add('shared_files', []);
add('shared_dirs', ['node_modules', 'public/content', 'config/jwt', 'config/app']);
add('writable_dirs', []);

// Hosts

host('dw1.srv.domainwatchdog.eu')
    ->setPort(2004)
    ->set('remote_user', 'deploy')
    ->set('deploy_path', '/var/www/demo.domainwatchdog.eu');

desc('Build frontend');
task('front:build', function () {
    run("cd {{release_or_current_path}} && yarn install --no-dev && yarn run build && yarn run ttag:po2json && rm -rf node_modules");
});

desc('Restart workers');
task('workers:restart', function () {
    run("cd {{release_or_current_path}} && bin/console messenger:stop-workers");
});

// Hooks

after('deploy:failed', 'deploy:unlock');
after('deploy:vendors', 'database:migrate');
after('deploy:vendors', 'front:build');
after('deploy:unlock', 'workers:restart');