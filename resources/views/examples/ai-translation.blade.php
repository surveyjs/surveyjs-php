@extends('layouts.example')

@section('theme', 'creator')

@section('banner')
    @unless (config('surveyjs.ai.api_key'))
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <strong>No AI key:</strong> <code>POST /api/translate</code> answers <code>501</code> until you set <code>AI_API_KEY</code> (and optionally <code>AI_BASE_URL</code> and <code>AI_MODEL</code>) in <code>.env</code> and reload.
        </p>
    @endunless
@endsection

@section('try')
    <li>The <strong>Translations</strong> tab opens with Deutsch, Français and Español added, and Español selected. Press <strong>Auto-translate All</strong> (the language list is in the side panel, <strong>Languages</strong>), or tick another language to translate into it too.</li>
    <li>Open <strong>Requests</strong> and expand <code>POST /api/translate</code>: it carries the strings and the locales, and it is the only outbound request.</li>
    <li>Without <code>AI_API_KEY</code>, the endpoint answers <code>501</code>, and <strong>Requests</strong> shows its message.</li>
@endsection

@section('form')
    <div id="creator" class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
