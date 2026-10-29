@extends('layouts.example')

{{-- "Open the form" (?form) renders the saved definition with the Form Library, so it loads the form theme --}}
@section('theme', request()->has('form') ? 'survey' : 'creator')

@section('try')
    <li>As <strong>Alice</strong> (editor), change the form title in the Designer. Creator saves automatically (<code>autoSaveEnabled</code>): one <code>PUT /api/forms/support</code> answers <code>204</code>. Open <strong>Requests</strong> and expand it to see the definition sent.</li>
    <li>Press <strong>Open the form</strong>: a new tab renders the saved definition, with your new title.</li>
    <li>Switch the <strong>Demo user</strong> to <strong>Bob</strong> (viewer) and change anything: each save answers <code>403</code> from <code>Gate::authorize('edit-forms')</code>, and Creator shows the server's message: "You don't have editor rights".</li>
    <li>Select a question: the property grid's Data category has the <code>dbColumn</code> property from <a href="{{ route('example', 'relational-storage') }}">I.7</a>.</li>
@endsection

@section('actions')
    <a href="?form" target="_blank" class="box-border inline-flex h-9 items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-700 no-underline hover:bg-gray-100">
        Open the form
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
    </a>
@endsection

@section('form')
    {{-- ?form renders a form here: it takes the form's padding instead of Creator's height --}}
    <div id="creator" class="{{ request()->has('form') ? 'p-4 sm:p-6' : 'h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]' }}"></div>
@endsection
