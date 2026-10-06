<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// GET /api/forms/{id} is the I.2 endpoint (load-definition-variables-data.php): Creator reads its .definition.
// This file holds only the PUT, so LINT_DEFINITIONS=true can swap in lint-definition.php.

// #region sjs:III.1.server
// PUT /api/forms/{id} — store the definition as-is: it's one JSON document
Route::put('/api/forms/{id}', function (Request $request, string $id) {
    Gate::authorize('edit-forms');                                           // editing is an admin action: 403 otherwise
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $definition = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    DB::table('forms')->updateOrInsert(['key' => $id], [
        'json' => json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
    ]);

    return response()->noContent();                                          // the next page load renders the new version
});
// #endregion
