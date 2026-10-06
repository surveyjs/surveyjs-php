@extends('layouts.example')

@section('theme', 'creator')

@section('description')
    <p>Several authors work in Survey Creator on the same form and see each other's changes, selection and cursors. On the client it is one plugin, <code>CollaborationPlugin</code> from <code>survey-creator-core/collaboration</code>: every edit becomes a small JSON record, and peers that apply the same records converge. Your server is a relay like the one in I.8, with one difference: it keeps each room's starting definition and an append-only log of records in arrival order, and sends both to a newcomer. Saving is unchanged: Creator's <code>saveSurveyFunc</code> stores the definition through III.1, and the next session starts from it.</p>
    <p>This relay is <code>app/Relay/EditRelay.php</code>, run as its own process with <code>php artisan relay:edit</code> (port {{ config('surveyjs.relay.edit_port') }}).</p>
@endsection

@section('try')
    <li><a data-invite-link target="_blank" href="#">Open a second window</a> with the invite link: each window shows the other's selection and edits live.</li>
    <li>Make a few edits, then open a <a data-invite-link target="_blank" href="#">third window</a>: it replays the saved definition plus the log and matches.</li>
    <li>Press <strong>Save</strong> in either window: <code>PUT /api/forms/support</code> stores it (III.1). When every window closes, the room empties, and a new session starts from the saved definition.</li>
@endsection

@section('controls')
    <div class="space-y-2 rounded-lg border border-gray-200 bg-white p-3 text-sm">
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

@section('form')
    <div id="creator" class="h-[80vh] min-h-[640px]"></div>
@endsection
