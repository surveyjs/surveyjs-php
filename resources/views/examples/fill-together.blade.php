@php
    $records = \App\Support\Demo::records('job-sheet', 'customer');
    $record = request()->query('record', array_key_first($records) ?? 'new');
@endphp
@extends('layouts.example')

@section('head')
    <script>window.SURVEYJS_PAGE.record = {{ Js::from((string) $record) }};</script>
@endsection

@section('description')
    <p>Several people fill in the same response at once and see each other's answers, focus and cursors. On the client it is one plugin, <code>CollaborationPlugin</code> from <code>survey-core/collaboration</code> (v3.1.1+). It emits small messages, applies incoming ones, and draws the participant bar, focus rings and cursors itself. Your server is a relay: it keeps each room's definition and the latest value of every answer, sends both to a newcomer in one <code>init</code> message, stores and forwards each change, and passes presence through. It never parses the definition or the answers. Last write wins per question. Saving the finished response stays the I.1 endpoint.</p>
    <p>This relay is <code>app/Relay/FillRelay.php</code>, run as its own process with <code>php artisan relay:fill</code> (port {{ config('surveyjs.relay.fill_port') }}).</p>
@endsection

@section('try')
    <li><a data-invite-link target="_blank" href="#">Open a second window</a> with the invite link. Both participants appear in the participant bar, with focus rings and cursors. Switch the demo user in one window to see Alice and Bob side by side.</li>
    <li>Answer a few questions, then open a <a data-invite-link target="_blank" href="#">third window</a>: it starts with those answers, from one <code>init</code> message.</li>
    <li>Type in the same question from two windows: the last write wins.</li>
    <li>Press <strong>Complete</strong> in one window: it posts through I.1, <code>POST /api/responses</code> in Requests.</li>
    <li>"What the server stored" shows the room's latest values as the relay keeps them (snapshots are written in demo mode).</li>
@endsection

@section('controls')
    <div class="space-y-2 rounded-lg border border-gray-200 bg-white p-3 text-sm">
        <div class="flex flex-wrap items-center gap-3">
            <span class="font-semibold text-gray-700">Record</span>
            <form method="get" class="flex items-center gap-2">
                <select name="record" onchange="this.form.submit()" class="rounded border border-gray-300 px-2 py-1">
                    @foreach ($records as $id => $label)
                        <option value="{{ $id }}" @selected((string) $id === (string) $record)>{{ $label }}</option>
                    @endforeach
                    <option value="new" @selected($record === 'new')>A new, blank job sheet</option>
                </select>
            </form>
            <span data-show="room" class="text-gray-500"></span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-gray-600">Invite link</span>
            <input data-invite-link readonly class="min-w-0 flex-1 rounded border border-gray-300 bg-gray-50 px-2 py-1 font-mono text-xs" onclick="this.select()">
            <button type="button" data-copy-invite class="cursor-pointer rounded border border-sjs px-2 py-1 text-sjs-dark data-[copied=true]:bg-sjs data-[copied=true]:text-white">Copy</button>
        </div>
        @if (config('surveyjs.demo_mode'))
            <p class="text-xs text-gray-500">The link carries your sandbox id: whoever opens it works in your copy of the demo data, in the same rooms.</p>
        @endif
    </div>
@endsection
