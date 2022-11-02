<?php

namespace Deployer;

require_once __DIR__ . '/common.php';

add('recipes', ['spiral']);

// Spiral shared dirs
set('shared_dirs', ['runtime']);

// Spiral writable dirs
set('writable_dirs', ['runtime', 'public']);

desc('Create .env file if it doesn\'t exist');
task('deploy:environment', function (): void {
    run('cd {{release_or_current_path}} && [ ! -f .env ] && cp .env.sample .env');
});

/**
 * Run a console command.
 *
 * Supported options:
 * - 'showOutput': Show the output of the command if given.
 */
function command(string $command, array $options = []): \Closure
{
    return function () use ($command, $options): void {
        $output = run("cd {{release_or_current_path}} && {{bin/php}} app.php $command");

        if (\in_array('showOutput', $options, true)) {
            writeln("<info>$output</info>");
        }
    };
}

/**
 * Spiral Framework console commands
 */
desc('Configure project');
task('spiral:configure', command('configure', ['showOutput']));

desc('Update (init) cycle schema from database and annotated classes');
task('spiral:cycle', command('cycle', ['showOutput']));

desc('Perform all outstanding migrations');
task('spiral:migrate', command('migrate', ['showOutput']));

desc('Update project state');
task('spiral:update', command('update', ['showOutput']));

desc('Clean application runtime cache');
task('spiral:cache:clean', command('cache:clean', ['showOutput']));

desc('Reset translation cache');
task('spiral:i18n:reset', command('i18n:reset', ['showOutput']));

desc('Generate new encryption key, if it doesn\'t exist');
task('deploy:encrypt-key', command('encrypt:key -m .env -p', ['showOutput']));

desc('Warm-up view cache');
task('views:compile', command('views:compile', ['showOutput']));

desc('Clear view cache');
task('views:reset', command('views:reset', ['showOutput']));

/**
 * Cycle ORM, and migrations console commands
 */
desc('Generate ORM schema migrations');
task('cycle:migrate', command('cycle:migrate', ['showOutput']));

desc('Render available CycleORM schemas');
task('cycle:render', command('cycle:render', ['showOutput']));

desc('Sync Cycle ORM schema with database without intermediate migration (risk operation)');
task('cycle:sync', command('cycle:sync', ['showOutput']));

desc('Init migrations component (create migrations table)');
task('migrate:init', command('migrate:init', ['showOutput']));

desc('Replay (down, up) one or multiple migrations');
task('migrate:replay', command('migrate:replay', ['showOutput']));

desc('Rollback one (default) or multiple migrations');
task('migrate:rollback', command('migrate:rollback', ['showOutput']));

desc('Get list of all available migrations and their statuses');
task('migrate:status', command('migrate:status', ['showOutput']));

/**
 * Run a RoadRunner console command.
 *
 * Supported options:
 * - 'showOutput': Show the output of the command if given.
 */
function rrCommand(string $command, array $options = []): \Closure
{
    return function () use ($command, $options): void {
        $output = run("cd {{release_or_current_path}} && {{bin/php}} ./vendor/bin/rr $command");

        if (\in_array('showOutput', $options, true)) {
            writeln("<info>$output</info>");
        }
    };
}

desc('Download RoadRunner');
task('deploy:roadrunner', rrCommand('get-binary', ['showOutput']));

desc('Restart RoadRunner');
task('deploy:serve', function (): void {
    // TODO pay attention on master process
    try {
        run('cd {{release_or_current_path}} && ./rr reset --silent');
        writeln("<info>Roadrunner successfully restarted.</info>");
    } catch (\Throwable $e) {
        writeln("<info>Roadrunner successfully started.</info>");
        $process = new Process(['./rr', 'serve']);
        $process->setOptions(['create_new_console' => true]);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->setWorkingDirectory(parse('{{release_or_current_path}}'));
        $process->start();
    }
});

/**
 * Main task
 */
desc('Deploys your project');
task('deploy', [
    'deploy:prepare',
    'deploy:environment',
    'deploy:vendors',
    'deploy:encrypt-key',
    'deploy:configure',
    'deploy:roadrunner',
    'deploy:publish',
    'deploy:serve'
]);
