<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:I.3.server
// GET /api/progress/{formId} — return the stored progress (answers + UI state) (404 if none yet)
Route::get('/api/progress/{formId}', function (string $formId) {
    $json = DB::table('progress')->where('key', $formId)->value('json') ?? abort(404, 'No progress saved yet');

    return response($json)->header('Content-Type', 'application/json');
});

// PUT /api/progress/{formId} — store the progress (answers + UI state) as-is: it's one JSON document
// Real code keys it by user and form, e.g. Auth::id().':'.$formId; the samples key by form for brevity
Route::put('/api/progress/{formId}', function (Request $request, string $formId) {
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $progress = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    StoredJson::put('progress', $formId, $progress);                         // stored as it arrived: 1.0 stays 1.0

    return response()->noContent();
});
// #endregion
