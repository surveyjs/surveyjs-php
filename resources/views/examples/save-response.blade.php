@extends('layouts.example')

@section('description')
    <p>The definition can live in the page as a constant: no database, no extra request. When the respondent completes the form, post the answers to one endpoint and store them exactly as they arrive: a response is a single JSON document.</p>
@endsection

@section('try')
    <li>Fill in the form and watch <strong>Requests</strong>: nothing is sent while you type. The definition is a constant in <code>shared/client/save-response.js</code>.</li>
    <li>Press <strong>Complete</strong>. Exactly one <code>POST /api/responses</code> appears.</li>
    <li>Compare <strong>Posted</strong> below with <strong>What the server stored</strong>: the check says <em>identical</em>. Try spaces around words, an empty message or non-ASCII text such as <code>naïve — 東京</code>.</li>
    <li><button id="start-over" type="button" class="text-sjs-dark underline">Start over</button> and send another one.</li>
@endsection

@section('controls')
    <details class="rounded-lg border border-gray-200 bg-white p-3 text-sm" open>
        <summary class="cursor-pointer font-semibold text-gray-700">Posted <span data-show="identical" class="ml-2 font-normal text-sjs-dark"></span></summary>
        <pre data-show="posted" class="mt-2 max-h-48 overflow-auto rounded bg-gray-50 p-2 font-mono text-xs">Nothing posted yet.</pre>
    </details>
@endsection
