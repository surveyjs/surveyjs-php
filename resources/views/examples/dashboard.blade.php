@extends('layouts.example')

@section('theme', 'dashboard')

@section('description')
    <p>SurveyJS Dashboard runs in the browser. Your backend returns the stored responses; the client hands them to the Dashboard together with the definition, and it picks a chart per question type. One endpoint returns the responses for a form. For large volumes, filter by date or paginate on the server instead of sending everything to the browser.</p>
@endsection

@section('try')
    <li>About 200 seeded feedback responses, spread over the last 90 days, render in the Dashboard.</li>
    <li>Change <strong>From</strong>: a new <code>GET /api/responses?formId=feedback&amp;from=…</code> appears in Requests, and "What the server stored" shows how many responses the server has since that date.</li>
    <li>The definition comes from <code>GET /api/forms/feedback</code>, the I.2 endpoint; the Dashboard reads its <code>.definition</code>.</li>
@endsection

@section('controls')
    <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm">
        <span class="font-semibold text-gray-700">From</span>
        <input id="from" type="date" class="rounded border border-gray-300 px-2 py-1">
        <span class="text-gray-500">responses created on or after this date (UTC)</span>
    </label>
@endsection

@section('form')
    <div id="dashboard" class="min-h-96 p-2"></div>
@endsection
