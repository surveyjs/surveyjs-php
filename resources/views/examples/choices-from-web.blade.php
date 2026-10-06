@extends('layouts.example')

@section('description')
    <p>A dropdown, checkbox or radio group loads its options from a URL with <code>choicesByUrl</code>, from a public web service or your own API. The URL can include answers and variables in curly braces, such as <code>/api/offices?region={region}</code>, and the list reloads when they change. The browser calls the URL directly, so a third-party service must allow cross-origin requests (CORS). If it doesn't, or it needs a secret key, call it through your own server, as <code>/api/countries</code> does. To send authentication headers, use <code>settings.web.onBeforeRequestChoices</code>.</p>
@endsection

@section('try')
    <li>Change the <strong>Region</strong>: a new <code>GET /api/offices?region=…</code> appears in Requests, and the Office list reloads.</li>
    <li>Open the <strong>Country</strong> list: it comes from <code>GET /api/countries</code>, the server's proxy. The log shows <code>X-Demo-Cache</code>: <em>miss</em> when the server fetched restcountries.com, <em>hit</em> from the 24-hour cache, <em>fallback</em> when the service failed and the bundled list was served (demo-only header).</li>
    <li>Every choices request carries <code>Authorization: Bearer demo-token</code>, added by <code>onBeforeRequestChoices</code>.</li>
@endsection

@section('banner')
    <p class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
        restcountries.com retired its keyless <code>v3.1</code> API, which now answers <code>{"success": false}</code>.
        The route treats that like an outage: it logs a warning and serves <code>shared/seed/countries.json</code> uncached, so the log shows <em>fallback</em>.
    </p>
@endsection
