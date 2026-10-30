@extends('layouts.example')

@section('theme', 'creator')

@section('try')
    <li>Press <strong>Open a second window</strong>: it opens the invite link, and each window shows the other's selection and edits live.</li>
    <li>Make a few edits, then press <strong>Open a second window</strong> again for a third: it replays the saved JSON schema plus the change log and matches.</li>
    <li>Open the <strong>Collaboration</strong> menu above Survey Creator's tabs and choose <strong>Show Version History</strong>: it lists every change in the room.</li>
    <li>Press <strong>Save</strong> in either window: <code>PUT /api/forms/support</code> stores it (3.1). When every window closes, the room empties, and a new session starts from the saved JSON schema.</li>
@endsection

@section('actions')
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
        <p>The invite link carries your sandbox ID: whoever opens it works in your copy of the demo data, in the same rooms.</p>
    @endif
@endsection

@section('form')
    <div id="creator" class="h-[calc(100dvh_-_var(--header-h,3.5rem)_-_var(--tabs-h,3.5rem)_-_var(--demo-above,1rem)_-_1.5rem)] min-h-[640px]"></div>
@endsection
