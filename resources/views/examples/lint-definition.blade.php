@extends('layouts.example')

@section('theme', 'creator')

@section('banner')
    @include('examples.partials.service-banner', ['switch' => 'LINT_DEFINITIONS', 'on' => config('surveyjs.lint_definitions')])
@endsection

@section('try')
    <li>Save a definition with <strong>An unknown variable in an expression</strong> (<code>visibleIf: "{nosuch} = 1"</code>) or <strong>A question without a name</strong>: Creator's notification shows the <code>422</code> errors, and nothing is saved.</li>
    <li>Save one with <strong>An unknown property</strong>: it is saved, and the notification shows the <code>200</code> warning.</li>
    <li><strong>A clean save</strong>, or Creator's own <strong>Save</strong>, returns <code>204</code>.</li>
@endsection

@section('actions')
    <span class="text-gray-600">Save a definition with</span>
    <button type="button" data-lint="unknown-variable" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown variable in an expression</button>
    <button type="button" data-lint="no-name" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A question without a name</button>
    <button type="button" data-lint="unknown-property" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">An unknown property</button>
    <button type="button" data-lint="clean" class="box-border inline-flex h-9 cursor-pointer items-center rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">A clean save</button>
@endsection

@section('form')
    <div id="creator" class="h-[calc(100dvh_-_var(--header-h,6rem)_-_var(--demo-above,1rem)_-_0.875rem)] min-h-[640px]"></div>
@endsection
