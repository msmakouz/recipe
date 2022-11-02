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

desc('Generate new encryption key, if it doesn\'t exist');
task('deploy:encrypt-key', command('encrypt:key -m .env -p', ['showOutput']));

desc('Warmup cache, configure permissions');
task('deploy:configure', command('configure', ['showOutput']));

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
