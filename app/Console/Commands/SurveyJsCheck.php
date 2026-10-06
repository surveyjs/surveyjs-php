<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Demo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Keeps the repository in step with the Server Integration page: the manifest, the snippet
 * regions, the shared/ files and the pinned SurveyJS versions.
 */
class SurveyJsCheck extends Command
{
    protected $signature = 'surveyjs:check
        {--complete : Also require all 18 steps of the page in the manifest}
        {--write-sums : Rewrite shared/SHA256SUMS (only while shared/ is a draft created in this repository)}';

    protected $description = 'Check the manifest, the snippet regions, shared/ and the SurveyJS versions';

    private const STEPS = ['I.1', 'I.2', 'I.3', 'I.4', 'I.5', 'I.6', 'I.7', 'I.8', 'II', 'III.1', 'III.2', 'III.3', 'III.4', 'III.5', 'IV.1', 'IV.2', 'IV.3', 'IV.4'];

    private const REGION_SOURCES = ['routes', 'app/Relay', 'shared/client'];

    private const MAX_REGION_LINES = 24;

    private const MAX_RELAY_REGION_LINES = 60;   // the relay region holds onConnect, onMessage and onClose

    private const SERVICE_SHA = 'db1ac24b5f984511bbac463c890b6b0a3ebd2dc9';

    private array $errors = [];

    public function handle(): int
    {
        $manifest = Demo::manifest();

        $this->checkShared();
        $this->checkVersions($manifest);
        $this->checkManifest($manifest);
        $this->checkRegions($manifest);

        if ($this->option('complete')) {
            foreach (array_diff(self::STEPS, array_keys($manifest['steps'])) as $missing) {
                $this->errors[] = "Step {$missing} is missing from surveyjs-integration.json";
            }
        }

        foreach ($this->errors as $error) {
            $this->components->error($error);
        }
        if ($this->errors) {
            return self::FAILURE;
        }

        $this->components->info(sprintf('OK: %d steps, regions, shared/ and versions are consistent.', count($manifest['steps'])));

        return self::SUCCESS;
    }

    private function checkShared(): void
    {
        $contract = trim((string) @file_get_contents(base_path('shared/CONTRACT')));
        if ($contract !== 'server-integration-v4') {
            $this->errors[] = "shared/CONTRACT must read server-integration-v4, found \"{$contract}\"";
        }

        $files = collect(File::allFiles(base_path('shared')))
            ->map(fn ($file) => str_replace('\\', '/', $file->getRelativePathname()))
            ->reject(fn (string $path) => in_array($path, ['SHA256SUMS', 'UPSTREAM'], true))
            ->sort()->values();
        $actual = $files->mapWithKeys(fn (string $path) => [$path => hash_file('sha256', base_path('shared/'.$path))]);

        if ($this->option('write-sums')) {
            if (! str_starts_with((string) @file_get_contents(base_path('shared/UPSTREAM')), 'draft:')) {
                $this->errors[] = 'shared/ is copied from surveyjs-nodejs: change it there, not here';

                return;
            }
            File::put(base_path('shared/SHA256SUMS'), $actual->map(fn ($hash, $path) => "{$hash}  {$path}")->implode("\n")."\n");
            $this->components->info('Wrote shared/SHA256SUMS');
        }

        $expected = collect(preg_split('/\R/', trim((string) @file_get_contents(base_path('shared/SHA256SUMS')))))
            ->filter()
            ->mapWithKeys(fn (string $line) => [substr($line, 66) => substr($line, 0, 64)]);

        foreach ($expected as $path => $hash) {
            if (! $actual->has($path)) {
                $this->errors[] = "shared/{$path} is listed in shared/SHA256SUMS but missing";
            } elseif ($actual[$path] !== $hash) {
                $this->errors[] = "shared/{$path} differs from shared/SHA256SUMS";
            }
        }
        foreach ($actual->keys()->diff($expected->keys()) as $path) {
            $this->errors[] = "shared/{$path} is not listed in shared/SHA256SUMS";
        }
    }

    private function checkVersions(array $manifest): void
    {
        $version = json_decode((string) file_get_contents(base_path('shared/surveyjs-version.json')), true)['version'] ?? null;
        $package = json_decode((string) file_get_contents(base_path('package.json')), true);

        if ($manifest['surveyjsVersion'] !== $version) {
            $this->errors[] = "surveyjsVersion in the manifest is {$manifest['surveyjsVersion']}, shared/surveyjs-version.json says {$version}";
        }
        foreach (['survey-core', 'survey-js-ui', 'survey-creator-core', 'survey-creator-js', 'survey-analytics', 'survey-pdf'] as $name) {
            $pinned = $package['dependencies'][$name] ?? null;
            if ($pinned !== $version) {
                $this->errors[] = "package.json pins {$name} to ".($pinned ?? 'nothing').", expected exactly {$version}";
            }
        }

        if (($manifest['surveyjsService'] ?? null) !== 'surveyjs/surveyjs-server@'.self::SERVICE_SHA) {
            $this->errors[] = 'surveyjsService in the manifest must be surveyjs/surveyjs-server@'.self::SERVICE_SHA;
        }
        $compose = base_path('docker-compose.yml');
        if (is_file($compose) && ! str_contains((string) file_get_contents($compose), 'surveyjs-server.git#'.self::SERVICE_SHA)) {
            $this->errors[] = 'docker-compose.yml must build surveyjs from surveyjs-server.git#'.self::SERVICE_SHA.', as the manifest records';
        }
    }

    private function checkManifest(array $manifest): void
    {
        foreach ($manifest['steps'] as $id => $step) {
            foreach ($step['files'] ?? [] as $role => $path) {
                if (! is_file(base_path($path))) {
                    $this->errors[] = "{$id}: {$role} file {$path} is missing";
                }
            }
            if (! in_array($step['status'], ['done', 'not-in-repo'], true)) {
                $this->errors[] = "{$id}: unknown status {$step['status']}";
            }
        }
    }

    private function checkRegions(array $manifest): void
    {
        $listed = collect($manifest['steps'])->flatMap(fn ($step) => $step['regions'])->all();
        $found = [];

        foreach (self::REGION_SOURCES as $dir) {
            foreach (File::allFiles(base_path($dir)) as $file) {
                $path = str_replace('\\', '/', $dir.'/'.$file->getRelativePathname());
                $this->checkMarkers($path);
                foreach (Demo::regionsIn($path) as $id => $region) {
                    $found[$id][] = $path;
                    $lines = $region['end'] - $region['start'] + 1;
                    $max = str_starts_with($path, 'app/Relay/') ? self::MAX_RELAY_REGION_LINES : self::MAX_REGION_LINES;
                    if ($lines > $max) {
                        $this->errors[] = "Region sjs:{$id} in {$path} has {$lines} lines (at most {$max})";
                    }
                    if (isset($region['duplicate'])) {
                        $this->errors[] = "Region sjs:{$id} appears {$region['duplicate']} times in {$path}";
                    }
                }
            }
        }

        foreach ($manifest['steps'] as $stepId => $step) {
            foreach ($step['regions'] as $id) {
                $files = $found[$id] ?? [];
                if (count($files) !== 1) {
                    $this->errors[] = "{$stepId}: region sjs:{$id} found ".count($files).' times, expected exactly once';
                } elseif (! in_array($files[0], [$step['files']['client'] ?? null, $step['files']['server'] ?? null], true)) {
                    $this->errors[] = "{$stepId}: region sjs:{$id} is in {$files[0]}, not in the step's client or server file";
                }
            }
        }
        foreach (array_diff(array_keys($found), $listed) as $id) {
            $this->errors[] = "Region sjs:{$id} in {$found[$id][0]} is not listed in the manifest";
        }
    }

    /** Regions never nest and always close. */
    private function checkMarkers(string $path): void
    {
        $open = false;
        foreach (preg_split('/\R/', (string) file_get_contents(base_path($path))) as $n => $line) {
            if (preg_match('~^\s*//\s*#region\b~', $line)) {
                if ($open) {
                    $this->errors[] = "{$path}:".($n + 1).': a region opens inside another region';
                }
                $open = true;
            } elseif (preg_match('~^\s*//\s*#endregion\b~', $line)) {
                if (! $open) {
                    $this->errors[] = "{$path}:".($n + 1).': #endregion without #region';
                }
                $open = false;
            }
        }
        if ($open) {
            $this->errors[] = "{$path}: a region is never closed";
        }
    }
}
