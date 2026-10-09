{{-- Demo only: the frame around every step page. The form, the Try this list, the requests log,
     "What the server stored" and the code panel. The integration code is in the step's files. --}}
@php
    $manifest = \App\Support\Demo::manifest();
    $config = \App\Support\Demo::pageConfig($stepId, $step);
    $users = \App\Http\Middleware\DemoUser::forPage($step['slug']);
    $commercial = str_starts_with($step['licence'], 'Commercial');
    $theme = trim($__env->yieldContent('theme', 'survey'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $stepId }} {{ $step['title'] }} · SurveyJS + Laravel</title>
    <script>window.SURVEYJS_PAGE = {{ Js::from($config) }};</script>
    @yield('head')
    @fonts
    @vite(['resources/css/app.css', "resources/css/{$theme}.css", 'resources/js/app.js', $step['files']['client']])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased">
<header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center gap-x-6 gap-y-2 px-4 py-3 sm:px-6">
        <a href="{{ route('catalog') }}" class="flex items-center gap-2 font-semibold text-gray-900 no-underline">
            <span class="rounded bg-sjs px-1.5 py-0.5 text-sm font-bold text-white">SurveyJS</span>
            <span>Server Integration · PHP / Laravel</span>
        </a>
        <span class="text-sm text-gray-500">{{ $stepId }} · {{ $step['title'] }}</span>
        @if (count($users) > 1)
            <label class="ml-auto flex items-center gap-2 text-sm">
                <span class="text-gray-500">Demo user</span>
                <select id="demo-user" class="rounded border border-gray-300 bg-white px-2 py-1 text-sm">
                    @foreach ($users as $key)
                        <option value="{{ $key }}" @selected($key === $config['demoUser'])>{{ \App\Http\Middleware\DemoUser::LABELS[$key] }}</option>
                    @endforeach
                </select>
            </label>
        @endif
    </div>
    @if (config('surveyjs.demo_mode'))
        <div class="border-t border-amber-200 bg-amber-50 px-4 py-1.5 text-center text-xs text-amber-900 sm:px-6">
            @if (request()->attributes->get('demo_join_failed'))
                <strong>That invite link has expired, so you have a fresh copy of the demo data.</strong>
            @endif
            You work in a private copy of the demo database, kept until it has been unused for 24 hours.
        </div>
    @endif
</header>

<main class="mx-auto grid max-w-screen-2xl gap-6 px-4 py-6 sm:px-6 xl:grid-cols-[minmax(0,1fr)_28rem]">
    <section class="min-w-0 space-y-5">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-sjs-dark">{{ $stepId }}</p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $step['title'] }}</h1>
            <p class="mt-2 text-sm"><strong class="text-gray-900">You need this:</strong> {{ $step['need'] }}</p>
            <div class="mt-3 max-w-3xl space-y-2 text-[15px] leading-relaxed text-gray-700 [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1 [&_code]:text-[13px] [&_a]:text-sjs-dark [&_a]:underline">
                @yield('description')
            </div>
            <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                <a class="text-sjs-dark underline" href="https://surveyjs.io/backend-integration/examples#{{ $step['anchor'] }}">This step on the Server Integration page</a>
                <a class="text-sjs-dark underline" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['server'] ?? $step['files']['client'] }}">Source on GitHub</a>
                @if ($commercial)
                    <span class="text-gray-500">{{ Str::before($step['licence'], ',') }} licence: without <code class="rounded bg-gray-100 px-1">SURVEYJS_LICENSE_KEY</code> the component shows a licence banner</span>
                @endif
            </p>
        </div>

        @yield('banner')

        <div class="rounded-lg border border-sjs/30 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Try this</h2>
            <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-[15px] [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1 [&_code]:text-[13px] [&_button]:cursor-pointer [&_a]:text-sjs-dark [&_a]:underline">
                @yield('try')
            </ol>
        </div>

        @yield('controls')

        <ul id="notes" hidden class="m-0 list-none space-y-1 p-0 text-sm"></ul>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            @section('form')
                <div id="form"></div>
            @show
        </div>

        <section class="min-w-0 pt-4">
            <h2 class="mb-3 text-lg font-semibold text-gray-900">Code that just ran</h2>
            <p class="mb-3 max-w-3xl text-sm text-gray-600">These regions are read from the files that served this page, the same lines the Server Integration page shows.</p>
            <div class="grid gap-4">
                @foreach (\App\Support\Demo::codePanel($step) as $region)
                    <figure class="min-w-0 overflow-hidden rounded-lg border border-gray-800 bg-[#0d1117]">
                        <figcaption class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-800 px-4 py-2 text-xs text-gray-300">
                            <span><span class="font-semibold text-white">{{ ucfirst($region['role']) }}</span> · <code>{{ $region['path'] }}</code> · <code>sjs:{{ $region['id'] }}</code></span>
                            <a class="text-sjs underline" href="{{ $region['url'] }}">View on GitHub</a>
                        </figcaption>
                        <pre class="m-0 overflow-x-auto p-0 text-[12.5px] leading-relaxed"><code class="language-{{ $region['language'] }} block !bg-transparent p-4">{{ $region['code'] }}</code></pre>
                    </figure>
                @endforeach
            </div>
            @isset($step['files']['definition'])
                <p class="mt-3 text-sm text-gray-600">Definition: <a class="text-sjs-dark underline" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['definition'] }}"><code>{{ $step['files']['definition'] }}</code></a>@isset($step['files']['definitionV2']), <a class="text-sjs-dark underline" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['definitionV2'] }}"><code>{{ $step['files']['definitionV2'] }}</code></a>@endisset</p>
            @endisset
        </section>
    </section>

    <aside class="min-w-0 space-y-6 xl:sticky xl:self-start xl:top-20 xl:max-h-[calc(100vh-6rem)] xl:overflow-y-auto">
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-4 py-2">
                <h2 class="text-sm font-semibold text-gray-900">Requests</h2>
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <label class="flex items-center gap-1"><input id="show-demo-requests" type="checkbox"> show demo requests</label>
                    <button id="clear-requests" type="button" class="cursor-pointer border-0 bg-transparent p-0 text-xs text-gray-500 underline">clear</button>
                </div>
            </div>
            <p id="requests-empty" class="px-4 py-3 text-sm text-gray-400">No requests yet.</p>
            <ol id="requests" class="m-0 max-h-80 list-none p-0 divide-y divide-gray-100 overflow-y-auto font-mono text-xs"></ol>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-4 py-2">
                <h2 class="text-sm font-semibold text-gray-900">What the server stored</h2>
                <span id="stored-updated" class="text-xs text-gray-400"></span>
            </div>
            <div id="stored" class="max-h-[32rem] space-y-3 overflow-y-auto p-4"></div>
        </section>
    </aside>

</main>

{{-- Rows that shared/client/host.js clones and fills by data-field --}}
<template id="request-row">
    <li class="grid grid-cols-[3.5rem_minmax(0,1fr)_auto] gap-x-2 px-4 py-1.5 data-[demo=true]:bg-gray-50 data-[demo=true]:text-gray-400 data-[state=error]:bg-red-50">
        <span data-field="method" class="font-semibold"></span>
        <span data-field="url" class="truncate" title=""></span>
        <span class="text-right"><span data-field="status" class="font-semibold"></span> <span data-field="time" class="text-gray-400"></span></span>
        <span data-field="headers" class="col-span-3 truncate text-[11px] text-sjs-dark empty:hidden"></span>
    </li>
</template>
<template id="stored-section">
    <div>
        <h3 data-field="title" class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500"></h3>
        <pre data-field="json" class="m-0 overflow-x-auto rounded bg-gray-50 p-2 font-mono text-[11.5px] leading-snug text-gray-800"></pre>
    </div>
</template>
<template id="note">
    <li data-field="text" class="rounded border px-3 py-1.5 data-[kind=error]:border-red-200 data-[kind=error]:bg-red-50 data-[kind=error]:text-red-800 data-[kind=info]:border-gray-200 data-[kind=info]:bg-white data-[kind=ok]:border-sjs/40 data-[kind=ok]:bg-emerald-50 data-[kind=ok]:text-emerald-900"></li>
</template>
</body>
</html>
