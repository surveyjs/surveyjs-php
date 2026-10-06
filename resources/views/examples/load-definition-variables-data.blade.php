@php
    // Demo only: this view also serves Option A (GET /claim in routes/examples/load-definition-variables-data.php)
    [$stepId, $step] = \App\Support\Demo::step('load-definition-variables-data');
    $optionA = isset($definition);
    $record = request()->query('record', '');
    $records = \App\Support\Demo::records('claim', 'customer_email');
@endphp
@extends('layouts.example')

@section('head')
    @if ($optionA)
        {{-- Option A: the server rendered the definition into the page, escaped for an inline script --}}
        <script>window.FORM_DEFINITION = {{ Js::from($definition) }};</script>
    @endif
@endsection

@section('description')
    <p>Three things can come from your server, each optional. The definition, rendered into the page as-is or with per-request changes, or fetched. Variables: application data the form reads in titles and conditions but never saves (the signed-in user, their plan, their locale). Previous answers: the stored response, so the form edits a record instead of starting blank.</p>
@endsection

@section('try')
    <li><strong>Option A</strong> (<a href="{{ url('/claim') }}">/claim</a>): the title says "Hi {{ \Illuminate\Support\Str::before(auth()->user()->name ?? 'there', ' ') }}" and Requests shows no definition request. The server rendered it into the page.</li>
    <li><strong>Option B</strong> (<a href="{{ route('example', 'load-definition-variables-data') }}">this page</a>): one <code>GET /api/forms/claim-request?record=</code> returns the definition, variables and previous answers.</li>
    <li>In Option B, switch the demo user: <code>{user.name}</code> in the title changes, and "use priority handling?" appears only for Alice's <strong>premium</strong> plan.</li>
    <li>Pick a record below: the form opens pre-filled and edits that claim instead of starting blank.</li>
    <li>Press <strong>Complete</strong>: the stored panel shows the answers and no variables. <button id="start-over" type="button" class="text-sjs-dark underline">Start over</button></li>
@endsection

@section('controls')
    <div class="flex flex-wrap items-center gap-4 rounded-lg border border-gray-200 bg-white p-3 text-sm">
        <span class="font-semibold text-gray-700">Option</span>
        <a href="{{ url('/claim') }}" @class(['rounded px-2 py-1', 'bg-sjs text-white' => $optionA, 'text-sjs-dark underline' => ! $optionA])>A: rendered into the page</a>
        <a href="{{ route('example', 'load-definition-variables-data') }}" @class(['rounded px-2 py-1', 'bg-sjs text-white' => ! $optionA, 'text-sjs-dark underline' => $optionA])>B: fetched</a>
        @unless ($optionA)
            <form method="get" class="ml-auto flex items-center gap-2">
                <label for="record" class="text-gray-600">Record</label>
                <select id="record" name="record" onchange="this.form.submit()" class="rounded border border-gray-300 px-2 py-1">
                    <option value="">None: a blank claim</option>
                    @foreach ($records as $id => $label)
                        <option value="{{ $id }}" @selected((string) $id === (string) $record)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        @endunless
    </div>
@endsection
