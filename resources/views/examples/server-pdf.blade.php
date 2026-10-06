@php
    $claims = \App\Support\Demo::records('claim', 'customer_email');
@endphp
@extends('layouts.example')

@section('description')
    <p>PDF Generator runs on plain Node.js, without a browser. Give it the definition and the answers; get back the filled form. Here the SurveyJS service produces it next to this app, and the route passes the bytes through.</p>
@endsection

@section('banner')
    @include('examples.partials.service-banner')
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        PDF Generator is commercial. The pinned SurveyJS service never calls <code>setLicenseKey()</code>, so its PDFs carry the SurveyJS alert banner even when you set a licence key.
    </p>
@endsection

@section('try')
    <li>Press <strong>Download PDF</strong> next to a claim: <code>GET /api/claims/&lt;id&gt;/pdf</code> answers <code>application/pdf</code>, and the filled form opens in a new tab.</li>
    <li>New claims from <a href="{{ route('example', 'relational-storage') }}">I.7</a> appear in this list.</li>
@endsection

@section('form')
    <ul class="m-0 list-none divide-y divide-gray-100 p-0">
        @forelse ($claims as $id => $label)
            <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                <span>Claim {{ $label }}</span>
                <button type="button" data-claim="{{ $id }}" class="cursor-pointer rounded bg-sjs px-3 py-1.5 font-semibold text-white">Download PDF</button>
            </li>
        @empty
            <li class="px-4 py-3 text-sm text-gray-500">No claims yet: submit one in I.7.</li>
        @endforelse
    </ul>
@endsection
