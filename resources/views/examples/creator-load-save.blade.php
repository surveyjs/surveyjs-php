@extends('layouts.example')

@section('theme', 'creator')

@section('description')
    <p>Survey Creator lets non-developers create and change forms in your application. For your backend, a definition is one more JSON document to load and save. Creator edits the definition your forms already load. Save it back through one endpoint; the next page load renders the new version. Saving definitions is an admin action: authorize it.</p>
@endsection

@section('try')
    <li>As <strong>Alice</strong> (editor), change the form title in the Designer and press <strong>Save</strong>: one <code>PUT /api/forms/support</code> answers <code>204</code>.</li>
    <li><a href="?form" target="_blank">Open the form</a>: it renders the saved definition, with your new title.</li>
    <li>Switch the demo user to <strong>Bob</strong> (viewer) and save again: Creator reports the save as failed, and Requests shows <code>403</code> from <code>Gate::authorize('edit-forms')</code>.</li>
    <li>Select a question: the property grid's Data category has the <code>dbColumn</code> property from <a href="{{ route('example', 'relational-storage') }}">I.7</a>.</li>
@endsection

@section('form')
    <div id="creator" class="h-[80vh] min-h-[640px]"></div>
@endsection
