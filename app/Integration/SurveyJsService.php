<?php

declare(strict_types=1);

namespace App\Integration;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls the SurveyJS service (SurveyJS Server, SURVEYJS_SERVICE_URL) for Section 4: 4.1 validates,
 * 4.2 lints, 4.3 prints a PDF and 4.4 extracts. It maps what isn't a service answer to the same
 * { error } bodies on every platform, so the routes only handle the answers they recognize. When you
 * copy one of those steps, copy this file too.
 */
final class SurveyJsService
{
    /**
     * POST JSON to the service. When it can't be reached, the request ends here: 504 when it timed
     * out (cURL error 28), 503 when it isn't running.
     */
    public static function post(string $path, mixed $body, int $timeout): Response
    {
        try {
            return Http::baseUrl(config('surveyjs.service_url'))->timeout($timeout)->post($path, $body);
        } catch (ConnectionException $e) {
            throw new HttpResponseException(str_contains($e->getMessage(), 'cURL error 28')
                ? response()->json(['error' => 'SurveyJS service timed out'], 504)
                : response()->json(['error' => 'SurveyJS service is not running at '.config('surveyjs.service_url')], 503));
        }
    }

    /** An answer the route doesn't recognize: log it and end the request with 502 { error }. Fail closed. */
    public static function unexpected(string $path, Response $answer, string $message = 'SurveyJS service error'): never
    {
        Log::error("SurveyJS service: unexpected answer from {$path}", ['status' => $answer->status(), 'body' => substr($answer->body(), 0, 2000)]);

        throw new HttpResponseException(response()->json(['error' => $message], 502));
    }
}
