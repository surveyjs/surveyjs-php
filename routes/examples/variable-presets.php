<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// #region sjs:3.4.server
// GET /api/variable-presets/{formId} — return the stored variable presets (404 if none yet)
Route::get('/api/variable-presets/{formId}', function (string $formId) {
    $json = DB::table('variable_presets')->where('key', $formId)->value('json') ?? abort(404, 'No presets saved yet');

    return response($json)->header('Content-Type', 'application/json');
});

// PUT /api/variable-presets/{formId} — store the variable presets as-is: it's one JSON document
Route::put('/api/variable-presets/{formId}', function (Request $request, string $formId) {
    Gate::authorize('edit-forms');                                           // editing is an admin action: 403 otherwise
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $presets = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    StoredJson::put('variable_presets', $formId, $presets);                  // stored as it arrived: 1.0 stays 1.0

    return response()->noContent();
});
// #endregion
