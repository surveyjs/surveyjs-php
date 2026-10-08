@extends('layouts.example')

@section('theme', 'creator')

@section('description')
    <p>Creator raises <code>onUploadFile</code> when an author adds an image or a file. Upload it to your storage and put the returned URL into the definition: the same <code>/api/files</code> endpoint as in <a href="{{ route('example', 'store-files') }}">I.4</a>, so there is no new server code. Creator saves automatically on this page.</p>
@endsection

@section('try')
    <li>In the Designer, click the logo area at the top of the form (<strong>Add logo</strong>... next to the title) and pick an image. Requests shows <code>POST /api/files</code>, then <code>PUT /api/forms/support</code>.</li>
    <li>"What the server stored" shows the definition's <code>logo</code> as <code>/api/files/&lt;id&gt;</code>: a URL, not base64.</li>
    <li>The logo in the Designer loads from <code>GET /api/files/&lt;id&gt;</code>, which checks access like any private file.</li>
@endsection

@section('form')
    <div id="creator" class="h-[80vh] min-h-[640px]"></div>
@endsection
