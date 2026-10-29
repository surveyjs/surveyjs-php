@php
    $records = \App\Support\Demo::records('job-sheet', 'customer');
    $record = request()->query('record', array_key_first($records) ?? 'new');
@endphp
@extends('layouts.example')

@section('head')
    <script>window.SURVEYJS_PAGE.record = {{ Js::from((string) $record) }};</script>
@endsection

@section('try')
    <li>Press <strong>Open a second window</strong>: it opens the invite link. Both participants appear in the participant bar, with focus rings and cursors.</li>
    <li>Answer a few questions, then press <strong>Open a second window</strong> again for a third: it starts with those answers, from one <code>init</code> message.</li>
    <li>Type in the same question from two windows: the last write wins.</li>
    <li>Press <strong>Complete</strong> in one window: <code>POST /api/responses</code> appears in <strong>Requests</strong>.</li>
    <li><strong>Data</strong> shows the <code>relay room</code>: the room's latest values as the relay keeps them (snapshots are written in demo mode).</li>
@endsection

@section('actions')
    <form method="get" class="flex items-center gap-2">
        <label for="record" class="text-gray-600">Record</label>
        <select id="record" name="record" onchange="this.form.submit()" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
            @foreach ($records as $id => $label)
                <option value="{{ $id }}" @selected((string) $id === (string) $record)>{{ $label }}</option>
            @endforeach
            <option value="new" @selected($record === 'new')>A new, blank job sheet</option>
        </select>
    </form>
    <span data-show="room" class="text-gray-500"></span>
    <span class="flex min-w-0 flex-1 items-center gap-2 basis-72">
        <label for="invite-link" class="shrink-0 text-gray-600">Invite link</label>
        <input id="invite-link" data-invite-link readonly class="box-border h-9 min-w-0 flex-1 rounded-md border border-gray-300 bg-gray-50 px-2 font-mono text-xs" onclick="this.select()">
        <button type="button" data-copy-invite class="box-border inline-flex h-9 shrink-0 cursor-pointer items-center rounded-md border border-sjs-dark bg-white px-3 text-sm font-semibold text-sjs-dark [font-family:inherit] data-[copied=true]:bg-sjs-dark data-[copied=true]:text-white">Copy</button>
    </span>
    <a data-invite-link target="_blank" href="#" class="box-border inline-flex h-9 items-center gap-1.5 rounded-md border border-sjs-dark bg-sjs-dark px-3 text-sm font-semibold text-white no-underline hover:bg-sjs-dark/90">
        Open a second window
        <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
    </a>
@endsection

@section('about')
    @if (config('surveyjs.demo_mode'))
        <p>The invite link carries your sandbox id: whoever opens it works in your copy of the demo data, in the same rooms.</p>
    @endif
@endsection
