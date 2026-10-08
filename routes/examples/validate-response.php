<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// Used in place of save-response.php when VALIDATE_RESPONSES=true (routes/examples.php).
// The service is SurveyJS Server, next to this app: see docker-compose.yml and config/surveyjs.php.

// #region sjs:IV.1.server
// POST /api/responses — ask the SurveyJS service to validate, then store as usual
Route::post('/api/responses', function (Request $request) {
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);    // the raw body keeps {} as {} (see I.1)
    $definition = DB::table('forms')->where('key', $body->formId ?? null)->value('json') ?? abort(404, 'Unknown form');
    try {
        $check = Http::baseUrl(config('surveyjs.service_url'))->timeout(10)
            ->post('/response', ['schema' => json_decode($definition), 'response' => $body->data ?? null]);
    } catch (ConnectionException $e) {                                         // cURL error 28 is a timeout
        return str_contains($e->getMessage(), 'cURL error 28')
            ? response()->json(['error' => 'SurveyJS service timed out'], 504)
            : response()->json(['error' => 'SurveyJS service is not running at '.config('surveyjs.service_url')], 503);
    }
    if ($check->status() === 422 && $check->json('errors') !== null && ! array_key_exists('warnings', $check->json())) {
        return response()->json(['errors' => $check->object()->errors], 400);    // the answers don't fit the definition
    }
    if ($check->status() !== 200 || $check->body() !== '{}') {                  // fail closed: only exactly {} means valid
        Log::error('SurveyJS service: unexpected answer from /response', ['status' => $check->status(), 'body' => $check->body()]);
        abort(502, $check->status() === 422 && $check->json('warnings') !== null ? 'The stored definition has errors' : 'SurveyJS service error');
    }
    $id = DB::table('responses')->insertGetId(['form_id' => $body->formId, 'created_at' => now('UTC')->toIso8601ZuluString(),
        'data' => json_encode($body->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)]);

    return response()->json(['id' => $id], 201);
});
// #endregion
