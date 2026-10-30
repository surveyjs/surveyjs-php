@extends('layouts.example')

@section('theme', 'dashboard')

@section('try')
    <li>About 200 seeded feedback responses, spread over the last 90 days, render in Dashboard.</li>
    <li>Change <strong>From</strong>: a new <code>GET /api/responses?formId=feedback&amp;from=…</code> appears in <strong>Requests</strong>, and <strong>Data</strong> shows, in <code>since from</code>, how many responses the server has since that date.</li>
    <li>The JSON schema comes from <code>GET /api/forms/feedback</code>.</li>
@endsection

@section('actions')
    <label for="from" class="text-gray-600">From</label>
    <input id="from" type="date" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
    <span class="text-gray-500">on or after this date (UTC)</span>
@endsection

@section('form')
    <div id="dashboard" class="min-h-96 p-2"></div>
@endsection
