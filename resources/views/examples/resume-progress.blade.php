@extends('layouts.example')

@section('try')
    <li>Type in a few fields. <strong>Under the hood › Requests</strong> shows one <code>PUT /api/progress/onboarding</code> per pause, not one per keystroke, and <strong>Progress</strong> at the top of <strong>Under the hood</strong> says when it saved.</li>
    <li>Go to the next page: the page change saves too.</li>
    <li>Type a letter, then switch to another browser tab and come back at once: the <code>PUT</code> was flushed when the tab was hidden.</li>
    <li>Press <strong>Reload page</strong>: you are back on the same page, in the same input, with your answers.</li>
    <li>Press <strong>Start over</strong>: the form is empty again, on its first page, and one <code>PUT</code> saves that, so <strong>Reload page</strong> no longer brings your answers back.</li>
@endsection

@section('actions')
    <button id="reload" type="button" class="box-border inline-flex h-9 cursor-pointer items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 [font-family:inherit] hover:bg-gray-100">
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 4 21 10 15 10"/><path d="M20.5 15a9 9 0 1 1-2.1-9.4L21 10"/></svg>
        Reload page
    </button>
    @include('examples.partials.start-over')
@endsection

@section('readouts')
    <p class="text-sm text-gray-700"><span class="font-semibold">Progress:</span> <span data-show="saved"></span></p>
@endsection
