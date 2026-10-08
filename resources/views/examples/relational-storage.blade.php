@php
    $version = request()->query('version') === 'v2' ? 'v2' : 'v1';
@endphp
@extends('layouts.example')

@section('description')
    <p>Always keep the original response JSON and the version of the definition it was filled against. With both, you can re-map old responses whenever your tables change. Then copy the answers you query into columns. <code>valueName</code> makes this safe: it sets the key a question writes into the response independently of the question's name, so a form author can rename or restructure questions in Creator without breaking your columns. For more mapping metadata, such as a column type or a target table, add a custom property to questions; it then appears in Creator's property grid too.</p>
@endsection

@section('try')
    <li>Fill in the <strong>v1</strong> form and press <strong>Complete</strong>. The stored panel shows the <code>responses</code> row with <code>definition_version: "v1"</code>, and the <code>claims</code> row with <code>customer_email</code> and <code>amount</code> filled.</li>
    <li>Switch to <strong>v2</strong>: the questions are renamed (<code>contact_email</code>, <code>claimed_total</code>) and moved into panels, but keep <code>valueName</code>. Submit again: the same columns fill.</li>
    <li>The custom <code>dbColumn</code> property is registered here and on the <a href="{{ route('example', 'creator-load-save') }}">III.1 Creator page</a>, where it shows in the property grid's Data category. <button id="start-over" type="button" class="text-sjs-dark underline">Start over</button></li>
@endsection

@section('controls')
    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 text-sm">
        <span class="font-semibold text-gray-700">Definition</span>
        @foreach (['v1' => 'v1: q_email, q_amount', 'v2' => 'v2: renamed in Creator'] as $v => $label)
            <a href="?version={{ $v }}" @class(['rounded px-2 py-1', 'bg-sjs text-white' => $version === $v, 'text-sjs-dark underline' => $version !== $v])>{{ $label }}</a>
        @endforeach
    </div>
@endsection
