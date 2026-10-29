<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use App\Integration\SurveyJsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

// Used in place of the PUT in creator-load-save.php when LINT_DEFINITIONS=true (routes/examples.php).

// #region sjs:4.2.server
// PUT /api/forms/{id} — lint the definition from Creator before storing it
Route::put('/api/forms/{id}', function (Request $request, string $id) {
    Gate::authorize('edit-forms');
    $definition = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);   // the raw body keeps {} as {} (see 3.1)
    $lint = SurveyJsService::post('/schema', $definition, timeout: 10);
    if ($lint->status() === 422 && $lint->json('errors') !== null) {           // errors: refuse the save, report them as they are
        return response($lint->body(), 422)->header('Content-Type', 'application/json');
    }
    $warningsOnly = $lint->status() === 200 && array_keys((array) $lint->json()) === ['warnings'];
    if (! $warningsOnly && ($lint->status() !== 200 || $lint->body() !== '{}')) {   // fail closed: nothing else counts as clean
        SurveyJsService::unexpected('/schema', $lint);
    }
    StoredJson::put('forms', $id, $definition);                                 // stored as it arrived: 1.0 stays 1.0

    return $warningsOnly ? response()->json(['warnings' => $lint->object()->warnings]) : response()->noContent();   // warnings don't block the save
});
// #endregion
