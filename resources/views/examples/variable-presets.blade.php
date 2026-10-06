@extends('layouts.example')

@section('theme', 'creator')

@section('description')
    <p>Variable presets (Creator v3.1.1) are named sets of sample variable values. Form authors switch between them in the Preview tab to test logic like "premium customers only", and use the variables in visual condition editors. Edits stay in memory until you save them: load and store the presets like any other JSON document.</p>
@endsection

@section('try')
    <li>In <strong>Preview</strong>, switch between the <strong>basic</strong> and <strong>premium</strong> presets: "Call me back within one hour" appears only for premium (<code>visibleIf: {customerTier} = 'premium'</code>).</li>
    <li>Edit a preset (change a value or add one): Requests shows one <code>PUT /api/variable-presets/support</code>. Just switching presets sends nothing.</li>
    <li>Reload the page: your edit is still there. As <strong>Bob</strong> the <code>PUT</code> answers <code>403</code>.</li>
@endsection

@section('form')
    <div id="creator" class="h-[80vh] min-h-[640px]"></div>
@endsection
