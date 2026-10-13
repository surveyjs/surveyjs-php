@extends('layouts.example')

@section('try')
    <li>Add one or two images or PDFs to <strong>Receipts</strong>. <strong>Under the hood › Requests</strong> shows one <code>POST /api/files</code>; the size line at the top of <strong>Under the hood</strong> compares the response size with base64 against the size with ids.</li>
    <li><strong>Under the hood › Stored</strong> lists the <code>files</code> by id, name and type. Press <strong>Complete</strong>: the stored response holds ids, not contents.</li>
    <li>The preview of each file comes through <code>GET /api/files/&lt;id&gt;</code> in <strong>Under the hood › Requests</strong>.</li>
    <li>Switch the <strong>Demo user</strong> to <strong>Signed out</strong>, then upload a file: the upload still answers <code>200</code>, but the preview's <code>GET /api/files/&lt;id&gt;</code> answers <code>404</code> (<code>Gate::denies('read-file')</code>).</li>
@endsection

@section('readouts')
    <p class="text-sm text-gray-700" data-show="sizes">Add a file to compare the response sizes.</p>
@endsection
