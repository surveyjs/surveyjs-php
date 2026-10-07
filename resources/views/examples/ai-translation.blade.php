@extends('layouts.example')

@section('theme', 'creator')

@section('description')
    <p>The Translations tab can request machine translation. Creator passes you the strings and the locales; your endpoint calls the AI provider you choose with your own key, and the author reviews the result. Nothing goes to SurveyJS.</p>
@endsection

@section('banner')
    @unless (config('surveyjs.ai.api_key'))
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <strong>501: Set AI_API_KEY to enable translation.</strong> Add <code>AI_API_KEY</code> (and optionally <code>AI_BASE_URL</code>, <code>AI_MODEL</code> for any OpenAI-compatible provider) to <code>.env</code>, then reload.
        </p>
    @endunless
@endsection

@section('try')
    <li>The <strong>Translations</strong> tab opens with Deutsch, Français and Español added, and Español selected. Press <strong>Auto-translate All</strong> (the language list is in the side panel, <strong>Languages</strong>), or tick another language to translate into it too.</li>
    <li>Requests shows one <code>POST /api/translate</code> with the strings and the locales: it is the only outbound request, and your server calls your AI provider.</li>
    <li>Without <code>AI_API_KEY</code>, the endpoint answers <code>501</code> and the page shows its message.</li>
@endsection

@section('form')
    <div id="creator" class="h-[80vh] min-h-[640px]"></div>
@endsection
