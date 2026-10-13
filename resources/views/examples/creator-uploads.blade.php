@extends('layouts.example')

@section('theme', 'creator')

@section('try')
    <li>In the Designer, click the logo area at the top of the form (<strong>Add logo</strong>... next to the title) and pick an image. <strong>Under the hood › Requests</strong> shows <code>POST /api/files</code>, then <code>PUT /api/forms/support</code>.</li>
    <li><strong>Under the hood › Stored</strong> shows the definition's <code>logo</code> as <code>/api/files/&lt;id&gt;</code>: a URL, not base64.</li>
    <li>The logo in the Designer loads from <code>GET /api/files/&lt;id&gt;</code>.</li>
@endsection

@section('form')
    <div id="creator" class="h-[calc(100dvh_-_var(--header-h,6rem)_-_var(--demo-above,1rem)_-_0.875rem)] min-h-[640px]"></div>
@endsection
