@extends('layouts.example')

@section('description')
    <p>The AI Form Response Extractor (MIT) takes the definition and a file, and returns answers in the form's own shape, ready to open in the form for a person to review. It calls the AI provider you configure with your own key; nothing is sent to SurveyJS. Here the SurveyJS service runs it next to this app; the route sends the scan as base64 JSON and saves nothing.</p>
@endsection

@section('banner')
    @include('examples.partials.service-banner')
    @unless (config('surveyjs.extraction.provider') || config('surveyjs.extraction.openai_api_key') || config('surveyjs.extraction.anthropic_api_key'))
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Without an AI provider the service answers <code>503</code> "The SurveyJS service has no AI provider configured". Set
            <code>SURVEYJS_AI_PROVIDER</code> (<code>openai</code>, <code>anthropic</code> or <code>ollama</code>) and its key or
            <code>SURVEYJS_OLLAMA_BASE_URL</code> in <code>.env</code>, then <code>docker compose up -d surveyjs</code>: Compose passes them to the service.
        </p>
    @endunless
@endsection

@section('try')
    <li><button id="sample-scan" type="button" class="cursor-pointer rounded bg-sjs px-2 py-0.5 text-sm font-semibold text-white">Use the sample scan</button>
        (<a href="{{ url('/shared/samples/work-order-scan.png') }}" target="_blank">work-order-scan.png</a>) or
        <label class="cursor-pointer text-sjs-dark underline">your own file<input id="own-scan" type="file" accept="image/*,application/pdf" class="hidden"></label>:
        one <code>POST /api/work-orders/extract</code> fills the form below as a draft.</li>
    <li>Answers read with low confidence are marked "Check this answer"; the unique id (from the QR code) is shown when found.</li>
    <li>"What the server stored" shows the same row counts before and after: extraction saves nothing.</li>
@endsection

@section('controls')
    <p data-show="extract-status" class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">No scan yet.</p>
@endsection
