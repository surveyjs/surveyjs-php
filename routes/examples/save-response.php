<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:I.1.server
// POST /api/responses — store the answers exactly as they arrive
Route::post('/api/responses', function (Request $request) {
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    abort_unless(isset($body->formId, $body->data), 400, 'Expected { formId, data }');

    $id = DB::table('responses')->insertGetId([
        'form_id' => $body->formId,
        'data' => json_encode($body->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),   // one JSON document
        'created_at' => now('UTC')->toIso8601ZuluString(),
    ]);

    return response()->json(['id' => $id], 201);
});
// #endregion
