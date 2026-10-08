@extends('layouts.example')

@section('description')
    <p>You need Section IV only if you can't trust the client: the browser already validates everything. Load the definition into a survey model on the server and set the submitted data; it reports answers that don't fit the definition: wrong types, unknown choices, missing required answers. Reject the save with the errors. Here the SurveyJS service does it, next to this app (<code>docker compose up -d surveyjs</code>).</p>
@endsection

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'VALIDATE_RESPONSES', 'on' => config('surveyjs.validate_responses'), 'replaces' => 'routes/examples/save-response.php'])
@endsection

@section('try')
    <li>Fill in the claim and press <strong>Complete</strong>: <code>POST /api/responses</code> answers <code>201</code> after the service validated it.</li>
    <li>Send tampered responses straight to the endpoint, bypassing the form:
        <button type="button" data-tamper="unknown-choice" class="ml-1 cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">an unknown choice</button>
        <button type="button" data-tamper="wrong-type" class="cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">a wrong type</button>
        <button type="button" data-tamper="missing-required" class="cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">a missing required answer</button>.
        Each answers <code>400</code> with the errors, and nothing is stored.</li>
@endsection
