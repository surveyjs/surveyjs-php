@extends('layouts.example')

@section('theme', 'creator')

@section('description')
    <p>Run survey-core's linter on every definition Creator saves: unknown properties, unknown variables, missing names, conflicting names. Refuse the save with the report, so a broken form never reaches your users. Warnings alone don't block the save: they are saved and shown. Here the SurveyJS service lints, next to this app.</p>
@endsection

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'LINT_DEFINITIONS', 'on' => config('surveyjs.lint_definitions'), 'replaces' => 'the PUT in routes/examples/creator-load-save.php'])
@endsection

@section('try')
    <li>Save a definition with
        <button type="button" data-lint="unknown-variable" class="cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">an unknown variable in an expression</button> (<code>visibleIf: "{nosuch} = 1"</code>) or
        <button type="button" data-lint="no-name" class="cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">a question without a name</button>:
        Creator's notification shows the <code>422</code> errors, and nothing is saved.</li>
    <li>Save one with <button type="button" data-lint="unknown-property" class="cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">an unknown property</button>: it is saved, and the notification shows the <code>200</code> warning.</li>
    <li>A <button type="button" data-lint="clean" class="cursor-pointer rounded border border-gray-300 bg-white px-2 py-0.5 text-sm">clean save</button>, or Creator's own <strong>Save</strong>, returns <code>204</code>.</li>
@endsection

@section('form')
    <div id="creator" class="h-[80vh] min-h-[640px]"></div>
@endsection
