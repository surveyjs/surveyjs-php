<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use App\Integration\SurveyJsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// Used in place of save-response.php when VALIDATE_RESPONSES=true (routes/examples.php).
// The service is SurveyJS Server, next to this app: see docker-compose.yml and config/surveyjs.php.

// #region sjs:4.1.server
// POST /api/responses — ask the SurveyJS service to validate, then store as usual
Route::post('/api/responses', function (Request $request) {
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);    // the raw body keeps {} as {} (see 1.1)
    $definition = DB::table('forms')->where('key', $body->formId ?? null)->value('json') ?? abort(404, 'Unknown form');
    $check = SurveyJsService::post('/response', ['schema' => json_decode($definition), 'response' => $body->data ?? null], timeout: 10);
    if ($check->status() === 422 && $check->json('errors') !== null && ! array_key_exists('warnings', $check->json())) {
        return response()->json(['errors' => $check->object()->errors], 400);    // the answers don't fit the definition
    }
    if ($check->status() !== 200 || $check->body() !== '{}') {                  // fail closed: only exactly {} means valid
        SurveyJsService::unexpected('/response', $check, $check->status() === 422 && $check->json('warnings') !== null ? 'The stored definition has errors' : 'SurveyJS service error');
    }
    $id = StoredJson::insertResponse($body->formId, $body->data);               // stored as it arrived: 1.0 stays 1.0

    return response()->json(['id' => $id], 201);
});
// #endregion
