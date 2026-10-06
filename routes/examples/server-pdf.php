<?php

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// #region sjs:IV.3.server
// GET /api/claims/{id}/pdf — ask the SurveyJS service for the filled form and pass the PDF through
Route::get('/api/claims/{id}/pdf', function (string $id) {
    $data = DB::table('responses')->where('form_id', 'claim')->where('id', $id)->value('data') ?? abort(404, 'Unknown claim');
    $definition = DB::table('forms')->where('key', 'claim')->value('json');
    try {
        $pdf = Http::baseUrl(config('surveyjs.service_url'))->timeout(60)
            ->post('/pdf', ['schema' => json_decode($definition), 'response' => json_decode($data)]);
    } catch (ConnectionException $e) {                                         // cURL error 28 is a timeout
        return str_contains($e->getMessage(), 'cURL error 28')
            ? response()->json(['error' => 'SurveyJS service timed out'], 504)
            : response()->json(['error' => 'SurveyJS service is not running at '.config('surveyjs.service_url')], 503);
    }
    if ($pdf->status() !== 200 || ! str_starts_with($pdf->header('Content-Type'), 'application/pdf')) {   // fail closed
        Log::error('SurveyJS service: unexpected answer from /pdf', ['status' => $pdf->status(), 'body' => substr($pdf->body(), 0, 2000)]);

        return response()->json(['error' => 'SurveyJS service error'], 502);
    }

    return response($pdf->body(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"claim-{$id}.pdf\"",
    ]);
});
// #endregion
