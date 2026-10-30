<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// GET /api/forms/{id} is in load-definition-variables-data.php: Creator reads its .definition.
// This file holds only the PUT, so LINT_DEFINITIONS=true can swap in lint-definition.php.

// #region sjs:creator-load-save.server
// PUT /api/forms/{id} — store the definition as-is: it's one JSON document
Route::put('/api/forms/{id}', function (Request $request, string $id) {
    Gate::authorize('edit-forms');                                           // editing is an admin action: 403 otherwise
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $definition = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    StoredJson::put('forms', $id, $definition);                              // stored as it arrived: 1.0 stays 1.0

    return response()->noContent();                                          // the next page load renders the new version
});
// #endregion
