@extends('layouts.example')

@section('try')
    <li>Fill in the form: <strong>Under the hood › Requests</strong> stays empty while you type. The definition is a constant in <code>shared/client/save-response.js</code>.</li>
    <li>Press <strong>Complete</strong>. Exactly one <code>POST /api/responses</code> appears: open <strong>Under the hood › Requests</strong> and expand it to see the posted body and the <code>201</code> answer, <code>{"id": …}</code>.</li>
    <li>The check at the top of <strong>Under the hood</strong> says the stored data is <em>identical</em> to the posted data, and <strong>Under the hood › Stored</strong> shows <code>responses</code> with it. Try spaces around words, an empty message or non-ASCII text such as <code>naïve — 東京</code>.</li>
    <li>Press <strong>Start over</strong> and send another one.</li>
@endsection

@section('actions')
    <button id="start-over" type="button" class="box-border inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 3 10 9 10"/><path d="M3.5 15a9 9 0 1 0 2.1-9.4L3 10"/></svg>
        Start over
    </button>
@endsection
