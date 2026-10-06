@extends('layouts.example')

@section('description')
    <p>Save the partial answers together with the survey's UI state (current page, active input) while the user works, and restore both when they return. Save on value changes and on page changes, debounced, and flush when the tab is hidden so closing it loses nothing. The server stores one JSON document per user and form; the samples key it by form for brevity.</p>
@endsection

@section('try')
    <li>Type in a few fields. Requests shows one <code>PUT /api/progress/onboarding</code> per pause, not one per keystroke, and the indicator below says when it saved.</li>
    <li>Go to the next page: the page change saves too.</li>
    <li>Type a letter, then switch to another browser tab and come back at once: the <code>PUT</code> was flushed when the tab was hidden.</li>
    <li><button id="reload" type="button" class="text-sjs-dark underline">Reload page</button>: you are back on the same page, in the same input, with your answers.</li>
    <li><button id="clear-progress" type="button" class="text-sjs-dark underline">Clear progress</button> to start over.</li>
@endsection

@section('controls')
    <p class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700">
        <span class="font-semibold">Progress:</span> <span data-show="saved"></span>
    </p>
@endsection
