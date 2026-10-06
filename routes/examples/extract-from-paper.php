<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// #region sjs:IV.4.server
// POST /api/work-orders/extract — send the uploaded scan to the SurveyJS service; return a draft response
Route::post('/api/work-orders/extract', function (Request $request) {
    $scan = $request->file('scan') ?? abort(400, 'Upload the scan in a field named "scan"');
    abort_if(! $scan->isValid() || $scan->getSize() > 5 * 1024 * 1024, 413, 'The scan must be a file of 5 MB or less');
    try {
        $out = Http::baseUrl(config('surveyjs.service_url'))->timeout(120)->post('/extract', [   // JSON with base64, not multipart
            'schema' => json_decode(DB::table('forms')->where('key', 'work-order')->value('json')), 'document' => base64_encode($scan->get())]);
    } catch (ConnectionException $e) {                                         // cURL error 28 is a timeout
        return str_contains($e->getMessage(), 'cURL error 28')
            ? response()->json(['error' => 'SurveyJS service timed out'], 504)
            : response()->json(['error' => 'SurveyJS service is not running at '.config('surveyjs.service_url')], 503);
    }
    $result = $out->object();

    return match (true) {                                                       // nothing is saved: a person reviews the draft
        $out->status() === 200 && is_object($result) && is_object($result->data ?? null) => response()->json(
            ['answers' => $result->data, 'confidence' => $result->confidence ?? [], 'uniqueId' => $result->uniqueId ?? null]),
        $out->status() === 400 && $out->json('error') === 'INVALID_DOCUMENT' => response()->json(['error' => 'The file is not a PDF, PNG, JPEG, WebP or GIF document'], 400),
        $out->status() === 503 && $out->json('error') === 'AI_NOT_CONFIGURED' => response()->json(['error' => 'The SurveyJS service has no AI provider configured'], 503),
        $out->status() === 502 && $out->json('error') === 'EXTRACTION_FAILED' => response()->json(['error' => 'Could not extract the response from the document'], 502),
        default => tap(response()->json(['error' => 'SurveyJS service error'], 502), fn () => Log::error('SurveyJS service: unexpected answer from /extract', ['status' => $out->status(), 'body' => substr($out->body(), 0, 2000)])),
    };
});
// #endregion
