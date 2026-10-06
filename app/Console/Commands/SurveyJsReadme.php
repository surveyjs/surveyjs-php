<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Demo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Regenerates the step table in README.md (between the "steps" markers) from surveyjs-integration.json.
 */
class SurveyJsReadme extends Command
{
    protected $signature = 'surveyjs:readme';

    protected $description = 'Regenerate the README step table from the manifest';

    public function handle(): int
    {
        $manifest = Demo::manifest();
        $rows = ['| Id | Step | Run | Server | Client | Page | Licence |', '|---|---|---|---|---|---|---|'];

        foreach ($manifest['steps'] as $id => $step) {
            $file = fn (string $role) => isset($step['files'][$role]) ? "[`{$step['files'][$role]}`]({$step['files'][$role]})" : '—';
            $rows[] = sprintf(
                '| %s | [%s](https://surveyjs.io/backend-integration/examples#%s) | [%s](%s%s) | %s | %s | %s | %s |',
                $id, $step['title'], $step['anchor'], $step['page'], $manifest['live'], $step['page'],
                $file('server'), $file('client'), $file('page'), $step['licence'],
            );
        }

        $readme = File::get(base_path('README.md'));
        $updated = preg_replace(
            '/(<!-- steps:start[^>]*-->\n).*?(\n<!-- steps:end -->)/s',
            '$1'.str_replace('$', '\$', implode("\n", $rows)).'$2',
            $readme,
            1,
            $count,
        );
        if ($count !== 1) {
            $this->components->error('README.md needs <!-- steps:start --> and <!-- steps:end --> markers.');

            return self::FAILURE;
        }

        File::put(base_path('README.md'), $updated);
        $this->components->info('Updated the step table in README.md ('.count($manifest['steps']).' steps).');

        return self::SUCCESS;
    }
}
