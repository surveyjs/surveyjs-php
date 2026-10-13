<?php

declare(strict_types=1);

use App\Integration\AiTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// #region sjs:III.3.server
// POST /api/translate — strings in, translated strings out (same order); nothing goes to SurveyJS
Route::post('/api/translate', function (Request $request) {
    abort_unless(config('surveyjs.ai.api_key'), 501, 'Set AI_API_KEY to enable translation');
    ['strings' => $strings, 'from' => $from, 'to' => $to] = $request->only(['strings', 'from', 'to']) + ['strings' => [], 'from' => '', 'to' => ''];

    $translated = AiTranslation::translate($strings, $from, $to);            // any OpenAI-compatible provider: your key, your choice
    abort_unless(is_array($translated) && count($translated) === count($strings), 502, 'The AI provider returned an unexpected answer');

    return response()->json($translated);                                    // what options.callback() takes
});
// #endregion
