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

@section('try')
    <li><strong>Option A</strong> (<a href="{{ url('/claim') }}">/claim</a>): the title says "Hi {{ \Illuminate\Support\Str::before(auth()->user()->name ?? 'there', ' ') }}" and <strong>Requests</strong> shows no definition request.</li>
    <li><strong>Option B</strong> (<a href="{{ route('example', 'load-definition-variables-data') }}">this page</a>): one <code>GET /api/forms/claim-request?record=</code> returns the definition, variables and previous answers. Open <strong>Requests</strong> and expand it to see them.</li>
    <li>In Option B, the title shows Alice's name and <strong>premium</strong> plan, and "use priority handling?" shows because Alice is on the <strong>premium</strong> plan.</li>
    <li>Pick a <strong>Record</strong>: the form opens pre-filled and edits that claim instead of starting blank.</li>
    <li>Press <strong>Complete</strong>: <strong>Data</strong> shows <code>responses</code> with the answers and no variables. Press <strong>Start over</strong> to fill it in again.</li>
@endsection

@section('actions')
    <span class="text-gray-600">Option</span>
    <span class="box-border inline-flex h-9 items-stretch rounded-md border border-gray-300 bg-white p-0.5">
        <a href="{{ url('/claim') }}" @if ($optionA) aria-current="page" @endif @class(['flex items-center rounded px-2.5 no-underline', 'bg-sjs-dark text-white' => $optionA, 'text-gray-700 hover:bg-gray-100' => ! $optionA])>A: rendered into the page</a>
        <a href="{{ route('example', 'load-definition-variables-data') }}" @unless ($optionA) aria-current="page" @endunless @class(['flex items-center rounded px-2.5 no-underline', 'bg-sjs-dark text-white' => ! $optionA, 'text-gray-700 hover:bg-gray-100' => $optionA])>B: fetched</a>
    </span>
    @unless ($optionA)
        <form method="get" class="flex items-center gap-2">
            <label for="record" class="text-gray-600">Record</label>
            <select id="record" name="record" onchange="this.form.submit()" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
                <option value="">None: a blank claim</option>
                @foreach ($records as $id => $label)
                    <option value="{{ $id }}" @selected((string) $id === (string) $record)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    @endunless
    @include('examples.partials.start-over')
@endsection
