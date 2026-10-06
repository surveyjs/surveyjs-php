<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Demo only: with DEMO_MODE=true, remove visitor sandboxes unused for 24 hours (`php artisan schedule:work`)
Schedule::command('demo:prune')->hourly()->when(fn () => config('surveyjs.demo_mode'));
