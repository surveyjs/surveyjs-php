<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

// #region sjs:III.3.server
// POST /api/translate — strings in, translated strings out (same order); nothing goes to SurveyJS
Route::post('/api/translate', function (Request $request) {
    abort_unless(config('surveyjs.ai.api_key'), 501, 'Set AI_API_KEY to enable translation');
    ['strings' => $strings, 'from' => $from, 'to' => $to] = $request->only(['strings', 'from', 'to']) + ['strings' => [], 'from' => '', 'to' => ''];

    $reply = Http::baseUrl(config('surveyjs.ai.base_url'))->withToken(config('surveyjs.ai.api_key'))->timeout(60)
        ->post('/chat/completions', [                                         // any OpenAI-compatible provider: your key, your choice
            'model' => config('surveyjs.ai.model'),
            'messages' => [
                ['role' => 'system', 'content' => "Translate each string from {$from} to {$to}. Return a JSON array in the same order."],
                ['role' => 'user', 'content' => json_encode(array_values($strings), JSON_UNESCAPED_UNICODE)],
            ],
        ])->throw();

    // Models sometimes wrap the array in prose or a code fence: take the array itself
    preg_match('/\[.*\]/s', (string) $reply->json('choices.0.message.content'), $match);
    $translated = json_decode($match[0] ?? 'null');
    abort_unless(is_array($translated) && count($translated) === count($strings), 502, 'The AI provider returned an unexpected answer');

    return response()->json($translated);                                    // what options.callback() takes
});
// #endregion
