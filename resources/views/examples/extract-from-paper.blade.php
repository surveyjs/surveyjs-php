@extends('layouts.example')

@section('banner')
    @unless (config('surveyjs.extraction.provider') || config('surveyjs.extraction.openai_api_key') || config('surveyjs.extraction.anthropic_api_key'))
        <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            <strong>No AI provider:</strong> extraction answers <code>503</code> until you set <code>SURVEYJS_AI_PROVIDER</code> (<code>openai</code>, <code>anthropic</code> or <code>ollama</code>) and its key or
            <code>SURVEYJS_OLLAMA_BASE_URL</code> in <code>.env</code>, then run <code>docker compose up -d surveyjs</code>.
        </p>
    @endunless
@endsection

@section('try')
    <li>Press <strong>Use the sample scan</strong> (<a href="{{ url('/shared/samples/work-order-scan.png') }}" target="_blank">work-order-scan.png</a>) or pick <strong>Your own file</strong>: one <code>POST /api/work-orders/extract</code> fills the form as a draft.</li>
    <li>Answers read with low confidence are marked "Check this answer"; the unique id (from the QR code) is shown when found.</li>
    <li><strong>Under the hood › Stored</strong> shows the same <code>row counts</code> before and after: extraction saves nothing.</li>
@endsection

@section('actions')
    <button id="sample-scan" type="button" class="box-border inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-sjs-dark bg-sjs-dark px-3 text-sm font-semibold text-white [font-family:inherit] hover:bg-sjs-dark/90">Use the sample scan</button>
    <label class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-sjs">
        Your own file<input id="own-scan" type="file" accept="image/*,application/pdf" class="sr-only">
    </label>
    <span data-show="extract-status" class="text-gray-700">No scan yet.</span>
@endsection
