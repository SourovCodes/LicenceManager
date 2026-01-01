<?php

namespace Deployer;

require 'recipe/laravel.php';

/*
|--------------------------------------------------------------------------
| Basic Config
|--------------------------------------------------------------------------
*/

set('repository', 'https://github.com/SourovCodes/LicenceManager.git');
set('branch', getenv('DEPLOY_BRANCH') ?: 'main');
set('keep_releases', 2);

/*
|--------------------------------------------------------------------------
| Laravel Shared & Writable Paths
|--------------------------------------------------------------------------
*/

set('shared_dirs', [
    'storage',
]);

set('shared_files', [
    '.env',
]);

set('writable_dirs', [
    'storage',
    'bootstrap/cache',
]);

set('writable_mode', 'chmod');

/*
|--------------------------------------------------------------------------
| Load Environment Variables
|--------------------------------------------------------------------------
*/

$hostname   = getenv('DEPLOY_HOSTNAME');
$deployPath = getenv('DEPLOY_PATH');
$sshPort    = getenv('DEPLOY_SSH_PORT');

if (! $hostname) {
    throw new \RuntimeException('DEPLOY_HOSTNAME environment variable is required');
}

if (! $deployPath) {
    throw new \RuntimeException('DEPLOY_PATH environment variable is required');
}

if (! $sshPort) {
    throw new \RuntimeException('DEPLOY_SSH_PORT environment variable is required');
}

/*
|--------------------------------------------------------------------------
| Hosts
|--------------------------------------------------------------------------
*/

host($hostname)
    ->set('remote_user', 'sourov')
    ->set('deploy_path', $deployPath)
    ->set('http_user', 'www-data')
    ->set('port', $sshPort);

/*
|--------------------------------------------------------------------------
| Local Asset Build
|--------------------------------------------------------------------------
*/

task('build:assets', function () {
    writeln('📦 Building assets locally...');
    runLocally('npm ci');
    runLocally('npm run build');
})->desc('Build assets locally');

/*
|--------------------------------------------------------------------------
| Upload Built Assets
|--------------------------------------------------------------------------
*/

task('upload:assets', function () {
    writeln('🚀 Uploading built assets...');
    $user        = get('remote_user');
    $hostname    = currentHost()->getHostname();
    $port        = get('port');
    $releasePath = get('release_path');
    $archive     = 'build-assets.tar.gz';

    runLocally("tar -czf {$archive} -C public build");
    runLocally("scp -P {$port} {$archive} {$user}@{$hostname}:{$releasePath}/");
    run("tar -xzf {$releasePath}/{$archive} -C {$releasePath}/public/");
    runLocally("rm {$archive}");
    run("rm {$releasePath}/{$archive}");
})->desc('Upload built assets');

/*
|--------------------------------------------------------------------------
| Skip npm on Server
|--------------------------------------------------------------------------
*/

task('deploy:npm', function () {
    writeln('⏭️  Skipping npm install on server');
});

/*
|--------------------------------------------------------------------------
| Fix Permissions After Deploy
|--------------------------------------------------------------------------
*/

task('permissions:fix', function () {
    run('chmod -R 775 {{release_path}}/storage {{release_path}}/bootstrap/cache');
})->desc('Fix Laravel writable permissions');

/*
|--------------------------------------------------------------------------
| Hooks
|--------------------------------------------------------------------------
*/

// Build assets locally before deployment starts
before('deploy', 'build:assets');

// Upload assets after vendors are installed
after('deploy:vendors', 'upload:assets');

// Fix permissions after symlink switch
after('deploy:symlink', 'permissions:fix');

// Unlock if deploy fails
after('deploy:failed', 'deploy:unlock');
