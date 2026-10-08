<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// #region sjs:I.5.server
// GET /api/offices?region= — {region} from the form arrives as a query parameter
Route::get('/api/offices', function (Request $request) {
    return DB::table('offices')->where('region', $request->query('region', ''))->orderBy('name')->get(['id', 'name']);
});

// GET /api/countries — a proxy: the browser can't call a service without CORS, your server can
Route::get('/api/countries', function () {
    try {
        $countries = Cache::remember('countries', now()->addDay(), fn () => Http::timeout(5)
            ->get('https://restcountries.com/v3.1/all', ['fields' => 'name,cca2'])->throw()->collect()
            ->map(fn (array $country) => ['code' => $country['cca2'], 'name' => $country['name']['common']])
            ->sortBy('name')->values()->all());
    } catch (Throwable $e) {   // down, or a different answer: serve the bundled list for 10 minutes, so requests don't each wait for the timeout
        Log::warning('restcountries.com failed; serving shared/seed/countries.json', ['error' => $e->getMessage()]);
        $countries = json_decode(file_get_contents(base_path('shared/seed/countries.json')), true);
        Cache::put('countries', $countries, now()->addMinutes(10));
    }

    return response()->json($countries)->header('Cache-Control', 'public, max-age=86400');
});
// #endregion
