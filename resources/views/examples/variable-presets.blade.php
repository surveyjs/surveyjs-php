@extends('layouts.example')

@section('theme', 'creator')

@section('try')
    <li>In <strong>Preview</strong>, switch between the <strong>basic</strong> and <strong>premium</strong> presets: "Call me back within one hour" appears only for premium (<code>visibleIf: {customerTier} = 'premium'</code>).</li>
    <li>Edit a preset (change a value or add one): <strong>Requests</strong> shows one <code>PUT /api/variable-presets/support</code>. Just switching presets sends nothing.</li>
    <li>Reload the page: your edit is still there.</li>
    <li>Switch the <strong>Demo user</strong> to <strong>Bob</strong>: editing a preset answers <code>403</code> from the same <code>Gate::authorize('edit-forms')</code>.</li>
@endsection

@section('form')
    <div id="creator" class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
