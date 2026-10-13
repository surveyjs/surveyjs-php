@php
    $version = request()->query('version') === 'v2' ? 'v2' : 'v1';
@endphp
@extends('layouts.example')

@section('try')
    <li>Fill in the <strong>v1</strong> form and press <strong>Complete</strong>. <strong>Under the hood › Stored</strong> shows the <code>responses</code> row with <code>definition_version: "v1"</code>, and the <code>claims</code> row with <code>customer_email</code> and <code>amount</code> filled.</li>
    <li>Switch the <strong>Definition</strong> to <strong>v2</strong>: the questions are renamed (<code>contact_email</code>, <code>claimed_total</code>) and moved into panels. Submit again: the same columns fill.</li>
    <li>Open the <a href="{{ route('example', 'creator-load-save') }}">III.1 Creator page</a> and select a question: the property grid's Data category shows <code>dbColumn</code>. Back here, press <strong>Start over</strong> to submit another claim.</li>
@endsection

@section('actions')
    <span class="text-gray-600">Definition</span>
    <span class="box-border inline-flex h-9 items-stretch rounded-md border border-gray-300 bg-white p-0.5">
        @foreach (['v1' => 'v1: q_email, q_amount', 'v2' => 'v2: renamed in Creator'] as $v => $label)
            <a href="?version={{ $v }}" @if ($version === $v) aria-current="page" @endif @class(['flex items-center rounded px-2.5 no-underline', 'bg-sjs-dark text-white' => $version === $v, 'text-gray-700 hover:bg-gray-100' => $version !== $v])>{{ $label }}</a>
        @endforeach
    </span>
    <button id="start-over" type="button" class="box-border inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 4 3 10 9 10"/><path d="M3.5 15a9 9 0 1 0 2.1-9.4L3 10"/></svg>
        Start over
    </button>
@endsection
