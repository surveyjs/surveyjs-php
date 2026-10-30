<?php

declare(strict_types=1);

namespace App\Integration;

use Illuminate\Support\Facades\Http;

/**
 * Asks any OpenAI-compatible /chat/completions endpoint (AI_BASE_URL, AI_API_KEY, AI_MODEL)
 * to translate Creator's strings. Your provider and your key: nothing goes to SurveyJS. Swap this
 * file for your provider's SDK if you like; the route only needs an array back. When you copy
 * ai-translation, copy this file too.
 */
final class AiTranslation
{
    /** The translated strings as the model returned them (an array if all went well), or null. */
    public static function translate(array $strings, mixed $from, mixed $to): mixed
    {
        $reply = Http::baseUrl(config('surveyjs.ai.base_url'))->withToken(config('surveyjs.ai.api_key'))->timeout(60)
            ->post('/chat/completions', [
                'model' => config('surveyjs.ai.model'),
                'messages' => [
                    ['role' => 'system', 'content' => "Translate each string from {$from} to {$to}. Return a JSON array in the same order."],
                    ['role' => 'user', 'content' => json_encode(array_values($strings), JSON_UNESCAPED_UNICODE)],
                ],
            ])->throw();

        // Models sometimes wrap the array in prose or a code fence: take the array itself
        preg_match('/\[.*\]/s', (string) $reply->json('choices.0.message.content'), $match);

        return json_decode($match[0] ?? 'null');
    }
}
