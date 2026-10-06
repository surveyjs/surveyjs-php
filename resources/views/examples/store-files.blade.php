@extends('layouts.example')

@section('description')
    <p>By default a file question embeds the file in the response as base64, which bloats every response. Set <code>storeDataAsText</code> to <code>false</code> and upload files to your own endpoint; the response keeps a reference instead of the content. If files are private, which is usual for documents, IDs and scans, store an id and add a second endpoint that checks access and returns the content. The form requests it through <code>onDownloadFile</code> when it shows a preview.</p>
@endsection

@section('try')
    <li>Add one or two images or PDFs to <strong>Receipts</strong>. Requests shows one <code>POST /api/files</code>; the line below compares the response size with base64 against the size with ids.</li>
    <li><strong>What the server stored</strong> lists the files by id, name and type. Press <strong>Complete</strong>: the stored response holds ids, not contents.</li>
    <li>The preview of each file comes through <code>GET /api/files/&lt;id&gt;</code> in Requests.</li>
    <li>Switch the demo user to <strong>Signed out</strong> before completing: the page reloads with the same answers, and the preview fails with <code>404</code> because the access check (<code>Gate::define('read-file')</code>) refuses it.</li>
@endsection

@section('controls')
    <p class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-700" data-show="sizes">Add a file to compare the response sizes.</p>
@endsection
