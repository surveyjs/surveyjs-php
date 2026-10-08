<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Used in place of the PUT in creator-load-save.php when LINT_DEFINITIONS=true (routes/examples.php).

// #region sjs:IV.2.server
// PUT /api/forms/{id} — lint the definition from Creator before storing it
Route::put('/api/forms/{id}', function (Request $request, string $id) {
    Gate::authorize('edit-forms');
    $definition = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);   // the raw body keeps {} as {} (see III.1)
    try {
        $lint = Http::baseUrl(config('surveyjs.service_url'))->timeout(10)->post('/schema', $definition);
    } catch (ConnectionException $e) {                                         // cURL error 28 is a timeout
        return str_contains($e->getMessage(), 'cURL error 28')
            ? response()->json(['error' => 'SurveyJS service timed out'], 504)
            : response()->json(['error' => 'SurveyJS service is not running at '.config('surveyjs.service_url')], 503);
    }
    if ($lint->status() === 422 && $lint->json('errors') !== null) {           // errors: refuse the save, report them as they are
        return response($lint->body(), 422)->header('Content-Type', 'application/json');
    }
    $warningsOnly = $lint->status() === 200 && array_keys((array) $lint->json()) === ['warnings'];
    if (! $warningsOnly && ($lint->status() !== 200 || $lint->body() !== '{}')) {   // fail closed: nothing else counts as clean
        Log::error('SurveyJS service: unexpected answer from /schema', ['status' => $lint->status(), 'body' => $lint->body()]);
        abort(502, 'SurveyJS service error');
    }
    DB::table('forms')->updateOrInsert(['key' => $id], ['json' => json_encode($definition,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)]);

    return $warningsOnly ? response()->json(['warnings' => $lint->object()->warnings]) : response()->noContent();
});
// #endregion
