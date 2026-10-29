@extends('layouts.example')

@section('try')
    <li>Fill in the form: <strong>Requests</strong> stays empty while you type. The definition is a constant in <code>shared/client/save-response.js</code>.</li>
    <li>Press <strong>Complete</strong>. Exactly one <code>POST /api/responses</code> appears: open <strong>Requests</strong> and expand it to see the posted body and the <code>201</code> answer, <code>{"id": …}</code>.</li>
    <li>The check at the top of <strong>Requests</strong> says the stored data is <em>identical</em> to the posted data, and <strong>Data</strong> shows <code>responses</code> with it. Try spaces around words, an empty message or non-ASCII text such as <code>naïve — 東京</code>.</li>
    <li>Press <strong>Start over</strong> and send another one.</li>
@endsection

@section('actions')
    @include('examples.partials.start-over')
@endsection
