@extends('layouts.example')

@section('try')
    <li>Add one or two images or PDFs to <strong>Receipts</strong>. <strong>Requests</strong> shows one <code>POST /api/files</code>; the size line at the top of <strong>Requests</strong> compares the response size with base64 against the size with ids.</li>
    <li><strong>Data</strong> lists the <code>files</code> by id, name and type. Press <strong>Complete</strong>: the stored response holds ids, not contents.</li>
    <li>The preview of each file comes through <code>GET /api/files/&lt;id&gt;</code> in <strong>Requests</strong>.</li>
    <li>Switch the <strong>Demo user</strong> to <strong>Signed out</strong>, then upload a file: the upload still answers <code>200</code>, but the preview's <code>GET /api/files/&lt;id&gt;</code> answers <code>404</code> (<code>Gate::denies('read-file')</code>).</li>
    <li>Press <strong>Start over</strong> to fill in another expense report.</li>
@endsection

@section('actions')
    @include('examples.partials.start-over')
@endsection

@section('readouts')
    <p class="text-sm text-gray-700" data-show="sizes">Add a file to compare the response sizes.</p>
@endsection
