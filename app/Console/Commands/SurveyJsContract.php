<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Runs shared/contract-tests/run.mjs against a fresh copy of this app: migrates and seeds a new
 * SQLite database, starts the app and both relays on free ports, runs the test, stops everything
 * and exits with the test's code. SERVICE, AI, VALIDATE_RESPONSES, LINT_DEFINITIONS and DEMO_MODE
 * pass through from the environment.
 */
class SurveyJsContract extends Command
{
    protected $signature = 'surveyjs:contract {--steps= : Only these steps, e.g. I.1,I.5}';

    protected $description = 'Run the shared contract test against a fresh database, the app and both relays';

    /** @var Process[] */
    private array $processes = [];

    public function handle(): int
    {
        $database = storage_path('app/contract/database.sqlite');
        File::ensureDirectoryExists(dirname($database));
        File::put($database, '');

        $env = [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'true',
            'LOG_CHANNEL' => 'single',
        ];
        foreach (['SERVICE', 'AI', 'VALIDATE_RESPONSES', 'LINT_DEFINITIONS', 'DEMO_MODE', 'SURVEYJS_SERVICE_URL', 'AI_BASE_URL', 'AI_API_KEY', 'AI_MODEL'] as $name) {
            if (($value = getenv($name)) !== false) {
                $env[$name] = $value;
            }
        }

        $this->components->task('Migrating and seeding a fresh database', fn () => $this->runOnce([PHP_BINARY, 'artisan', 'migrate:fresh', '--seed', '--force'], $env));

        [$appPort, $fillPort, $editPort] = [$this->freePort(), $this->freePort(), $this->freePort()];
        $env += ['RELAY_PORT' => (string) $fillPort, 'EDIT_RELAY_PORT' => (string) $editPort, 'APP_URL' => "http://127.0.0.1:{$appPort}"];

        try {
            $this->start('app', [PHP_BINARY, '-d', 'upload_max_filesize=10M', '-d', 'post_max_size=12M', '-S', "127.0.0.1:{$appPort}", '-t', 'public', 'server.php'], $env, $appPort);
            $this->start('fill relay', [PHP_BINARY, 'artisan', 'relay:fill'], $env, $fillPort);
            $this->start('edit relay', [PHP_BINARY, 'artisan', 'relay:edit'], $env, $editPort);

            $test = new Process(
                array_filter(['node', 'shared/contract-tests/run.mjs', $this->option('steps') ? '--steps='.$this->option('steps') : null]),
                base_path(),
                $env + [
                    'BASE_URL' => "http://127.0.0.1:{$appPort}",
                    'RELAY_URL' => "ws://127.0.0.1:{$fillPort}",
                    'EDIT_RELAY_URL' => "ws://127.0.0.1:{$editPort}",
                ],
                null,
                null,
            );
            $code = $test->run(fn ($type, $output) => $this->output->write($output));
        } finally {
            foreach ($this->processes as $name => $process) {
                $process->stop(2);
                if ($code ?? 1) {
                    $errors = trim($process->getErrorOutput()."\n".$process->getOutput());
                    if ($errors !== '') {
                        $this->line("<comment>--- {$name} output</comment>\n".mb_substr($errors, -3000));
                    }
                }
            }
        }

        return $code;
    }

    private function runOnce(array $command, array $env): bool
    {
        $process = new Process($command, base_path(), $env, null, 300);
        $process->mustRun();

        return true;
    }

    private function start(string $name, array $command, array $env, int $port): void
    {
        $process = new Process($command, base_path(), $env, null, null);
        $process->start();
        $this->processes[$name] = $process;

        $deadline = microtime(true) + 20;
        while (microtime(true) < $deadline) {
            if (! $process->isRunning()) {
                throw new \RuntimeException("The {$name} stopped: ".$process->getErrorOutput().$process->getOutput());
            }
            $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.2);
            if ($socket) {
                fclose($socket);
                $this->components->twoColumnDetail("Started the {$name}", "port {$port}");

                return;
            }
            usleep(200_000);
        }
        throw new \RuntimeException("The {$name} did not start on port {$port}");
    }

    private function freePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        $port = (int) substr(strrchr((string) stream_socket_get_name($server, false), ':'), 1);
        fclose($server);

        return $port;
    }
}
