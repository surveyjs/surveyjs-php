<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// #region sjs:1.1.server
// POST /api/responses — store the answers exactly as they arrive
// bootstrap/app.php turns off TrimStrings and ConvertEmptyStringsToNull for /api/*: they would change "  a " and ""
Route::post('/api/responses', function (Request $request) {
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    abort_unless(isset($body->formId, $body->data), 400, 'Expected { formId, data }');

    $id = StoredJson::insertResponse($body->formId, $body->data);   // one JSON document, stored as it arrived: 1.0 stays 1.0

    return response()->json(['id' => $id], 201);
});
// #endregion
