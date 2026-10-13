@extends('layouts.example')

@section('try')
    <li>In <strong>Find an office</strong>, change the <strong>Region</strong>: a new <code>GET /api/offices?region=…</code> appears in <strong>Under the hood › Requests</strong>, and the Office list reloads.</li>
    <li>Open the <strong>Country</strong> list: it comes from <code>GET /api/countries</code>. <strong>Under the hood › Requests</strong> shows <code>X-Demo-Cache</code>: <em>miss</em> when the server fetched restcountries.com, <em>hit</em> from the 24-hour cache, <em>fallback</em> when the service failed and the bundled list was served (demo-only header).</li>
    <li>Every choices request carries <code>Authorization: Bearer demo-token</code>.</li>
    <li>Press <strong>Start over</strong>: the Region goes back to Europe, and the Office list reloads for it.</li>
@endsection

@section('actions')
    @include('examples.partials.start-over')
@endsection

@section('banner')
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        restcountries.com retired its keyless <code>v3.1</code> API, so <code>/api/countries</code> serves the bundled <code>shared/seed/countries.json</code> and Requests shows <em>fallback</em>.
    </p>
@endsection
