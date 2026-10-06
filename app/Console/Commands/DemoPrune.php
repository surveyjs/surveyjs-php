<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Sandbox;
use Illuminate\Console\Command;

/**
 * Demo only: removes visitor sandboxes (DEMO_MODE=true) that nobody has used for 24 hours.
 * Scheduled hourly in routes/console.php; a visitor who is still working keeps their data.
 */
class DemoPrune extends Command
{
    protected $signature = 'demo:prune';

    protected $description = 'Remove demo sandboxes unused for 24 hours';

    public function handle(): int
    {
        $removed = Sandbox::prune();
        $this->components->info("Removed {$removed} sandbox(es) unused for ".config('surveyjs.demo.sandbox_ttl_hours').' hours.');

        return self::SUCCESS;
    }
}
