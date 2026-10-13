@extends('layouts.example')

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'VALIDATE_RESPONSES', 'on' => config('surveyjs.validate_responses')])
@endsection

@section('try')
    <li>Fill in the claim and press <strong>Complete</strong>: <code>POST /api/responses</code> answers <code>201</code> after the service validated it.</li>
    <li>Send tampered responses straight to the endpoint, bypassing the form, with <strong>An unknown choice</strong>, <strong>A wrong type</strong> or <strong>A missing required answer</strong>. Each answers <code>400</code> with the errors, shown at the top of <strong>Under the hood</strong>, and nothing is stored.</li>
@endsection

@section('actions')
    <span class="text-gray-600">Send a tampered response</span>
    <button type="button" data-tamper="unknown-choice" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown choice</button>
    <button type="button" data-tamper="wrong-type" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A wrong type</button>
    <button type="button" data-tamper="missing-required" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A missing required answer</button>
@endsection
