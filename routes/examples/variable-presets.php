<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// #region sjs:III.4.server
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
    DB::table('variable_presets')->updateOrInsert(['key' => $formId], [
        'json' => json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
    ]);

    return response()->noContent();
});
// #endregion
