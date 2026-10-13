{{-- Demo only: the frame around every step page. A sticky header with three tabs (resources/js/page-tabs.js):
     Demo (the action row and the component), Description (what to try, the endpoints and the files) and
     Under the hood (the code that ran, the requests and what the server stored). The explanation is in the
     docs; the integration code is in the step's files. --}}
@php
    $manifest = \App\Support\Demo::manifest();
    $config = \App\Support\Demo::pageConfig($stepId, $step);
    $users = \App\Http\Middleware\DemoUser::forPage($step['slug']);
    $commercial = str_starts_with($step['licence'], 'Commercial');
    $theme = trim($__env->yieldContent('theme', 'survey'));
    $blob = config('surveyjs.github').'/blob/'.$manifest['branch'];
    $links = \App\Support\Demo::liveLinks($step, request());
    $hasActions = count($users) > 1 || trim($__env->yieldContent('actions')) !== '';
    $hasReadouts = trim($__env->yieldContent('readouts')) !== '';
    $licence = Str::contains($step['licence'], ',') ? Str::before($step['licence'], ',').' licence,'.Str::after($step['licence'], ',') : $step['licence'].' licence';
    $tabs = [
        'demo' => ['Demo', '<polygon points="6 4 20 12 6 20 6 4"/>'],
        'description' => ['Description', '<path d="M4 5h16M4 10h16M4 15h10M4 20h7"/>'],
        'hood' => ['Under the hood', '<polyline points="8 7 3 12 8 17"/><polyline points="16 7 21 12 16 17"/>'],
    ];
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
    @vite(['resources/css/app.css', "resources/css/{$theme}.css", 'resources/js/app.js', 'resources/js/page-tabs.js', $step['files']['client']])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased">
<header id="page-header" class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex min-w-0 max-w-screen-2xl items-center gap-3 px-3 pb-0.5 pt-2.5 sm:px-6">
        <a href="{{ route('catalog') }}" class="flex shrink-0 items-center gap-2 font-semibold text-gray-900 no-underline">
            <span class="rounded bg-sjs px-1.5 py-0.5 text-sm font-bold text-white">SurveyJS</span>
            <span class="max-sm:hidden">Server Integration · PHP / Laravel</span>
        </a>
        <span aria-hidden="true" class="text-gray-300 max-sm:hidden">/</span>
        <h1 class="min-w-0 truncate text-base font-semibold text-gray-900"><span class="font-bold text-sjs-dark">{{ $stepId }}</span> {{ $step['title'] }}</h1>
    </div>

    {{-- Row 2 never wraps: page-tabs.js moves the live links that don't fit into "More (n)" --}}
    <div class="mx-auto flex max-w-screen-2xl items-stretch gap-3 px-3 sm:px-6">
        <div id="page-tabs" role="tablist" aria-label="Step views" class="flex shrink-0 items-stretch gap-1 max-sm:flex-1">
            @foreach ($tabs as $key => [$label, $icon])
                <button type="button" role="tab" id="tab-{{ $key }}" data-tab="{{ $key }}" aria-controls="panel-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}"
                        class="flex h-11 cursor-pointer items-center gap-2 whitespace-nowrap border-0 bg-transparent px-3.5 text-[15px] font-semibold text-gray-600 shadow-[inset_0_-3px_0_transparent] [font-family:inherit] hover:text-gray-900 aria-selected:text-gray-900 aria-selected:shadow-[inset_0_-3px_0_var(--color-sjs)] max-sm:flex-1 max-sm:justify-center max-sm:gap-1.5 max-sm:px-1 max-sm:text-[13px]">
                    <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icon !!}</svg>
                    {{ $label }}
                    @if ($key === 'hood')
                        <span id="hood-badge" hidden data-kind="ok" class="min-w-5 rounded-full bg-sjs-dark px-1.5 text-center text-xs leading-5 text-white data-[kind=error]:bg-red-600"></span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- One link per logged endpoint and per stored section; page-tabs.js keeps their status pills current --}}
        <nav id="live-links" aria-label="Requests and stored data" class="flex min-w-0 flex-1 items-center gap-1 overflow-x-auto max-md:hidden data-[fit]:overflow-hidden [&>:first-child]:ml-auto">
            @foreach ($links as $link)
                @include('examples.partials.live-link', ['link' => $link])
            @endforeach
        </nav>
        <div id="live-more" hidden class="relative flex shrink-0 items-center max-md:hidden">
            <button type="button" id="live-more-button" aria-expanded="false" aria-controls="live-more-list"
                    class="flex h-8 cursor-pointer items-center gap-1 rounded-md border-0 bg-transparent px-2 text-sm font-semibold text-gray-600 [font-family:inherit] hover:bg-gray-100 hover:text-gray-900 aria-expanded:bg-gray-100">
                <span>More (<span data-more-count>0</span>)</span>
                <svg aria-hidden="true" class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <ul id="live-more-list" hidden class="absolute right-0 top-full z-50 m-0 mt-1 flex min-w-max list-none flex-col gap-0.5 rounded-lg border border-gray-200 bg-white p-1.5 shadow-lg">
                @foreach ($links as $link)
                    <li hidden data-more-item>@include('examples.partials.live-link', ['link' => $link])</li>
                @endforeach
            </ul>
        </div>
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

<main>
    {{-- DEMO: setup warnings, the action row and the component, full width minus the gutter --}}
    <div id="panel-demo" role="tabpanel" aria-labelledby="tab-demo" class="space-y-3 px-4 py-3 sm:px-6">
        @yield('banner')

        @if ($hasActions)
            <div id="actions" class="flex flex-wrap items-center gap-2 text-sm">
                @if (count($users) > 1)
                    <label class="flex items-center gap-2">
                        <span class="text-gray-600">Demo user</span>
                        <select id="demo-user" class="box-border h-9 rounded-md border border-gray-300 bg-white px-2 text-sm">
                            @foreach ($users as $key)
                                <option value="{{ $key }}" @selected($key === $config['demoUser'])>{{ \App\Http\Middleware\DemoUser::LABELS[$key] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @yield('actions')
            </div>
        @endif

        <div id="component" class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            @section('form')
                <div id="form"></div>
            @show
        </div>
    </div>

    {{-- DESCRIPTION: the reading matter --}}
    <div id="panel-description" role="tabpanel" aria-labelledby="tab-description" hidden class="mx-auto max-w-[880px] space-y-6 px-4 pb-12 pt-8 sm:px-6">
        <div class="space-y-2">
            <p class="text-sm font-bold uppercase tracking-wide text-sjs-dark">{{ $stepId }} · {{ $licence }}</p>
            <h2 class="text-2xl font-bold text-gray-900 sm:text-3xl">{{ $step['title'] }}</h2>
            <p><strong class="text-gray-900">You need this:</strong> {{ $step['need'] }}</p>
            <p><a class="text-sjs-dark underline" href="{{ \App\Support\Demo::docsUrl($step) }}">Read this step in the docs</a></p>
        </div>

        @if ($commercial)
            <p class="text-sm text-gray-600">{{ Str::before($step['licence'], ',') }} licence: without <code class="rounded bg-gray-100 px-1">SURVEYJS_LICENSE_KEY</code> the component shows a licence banner.</p>
        @endif

        <section class="space-y-3 rounded-lg border border-gray-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gray-600">Try this</h3>
                <button type="button" data-open-tab="demo" class="flex h-9 cursor-pointer items-center gap-1.5 rounded-md border-0 bg-sjs-dark px-3.5 text-sm font-semibold text-white [font-family:inherit] hover:bg-sjs-dark/90">
                    Open the demo
                    <svg aria-hidden="true" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="13 6 19 12 13 18"/></svg>
                </button>
            </div>
            <ol id="try" class="m-0 list-decimal space-y-2 pl-5 text-[15px] [&_a]:text-sjs-dark [&_a]:underline [&_code]:rounded [&_code]:bg-gray-100 [&_code]:px-1 [&_code]:text-[13px]">
                @yield('try')
            </ol>
        </section>

        <div class="grid gap-3 sm:grid-cols-2">
            <section class="space-y-1.5 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gray-600">{{ Str::plural('Endpoint', count($step['endpoints'])) }}</h3>
                <ul class="m-0 list-none space-y-1 p-0">
                    @foreach ($step['endpoints'] as $endpoint)
                        <li><code class="font-mono text-[13px]">{{ $endpoint }}</code></li>
                    @endforeach
                </ul>
            </section>
            <section class="space-y-1.5 rounded-lg border border-gray-200 bg-white p-4">
                <h3 class="text-sm font-bold uppercase tracking-wide text-gray-600">Files</h3>
                <ul class="m-0 list-none space-y-1 p-0">
                    @foreach (array_unique($step['files']) as $path)
                        <li class="min-w-0 break-all"><a class="font-mono text-[13px] text-sjs-dark underline" href="{{ $blob }}/{{ $path }}">{{ $path }}</a></li>
                    @endforeach
                </ul>
            </section>
        </div>

        @hasSection('about')
            <div class="space-y-2 text-sm text-gray-600">@yield('about')</div>
        @endif
    </div>

    {{-- UNDER THE HOOD: fills the viewport below the header; the sections scroll inside it.
         shared/client/host.js fills #notes, #requests and #stored by id and by the templates below, also while hidden. --}}
    <div id="panel-hood" role="tabpanel" aria-labelledby="tab-hood" hidden class="mx-auto box-border flex h-[calc(100dvh-var(--header-h,6rem))] max-w-screen-2xl flex-col px-4 py-3 text-sm sm:px-6">
        <div id="hood" class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            {{-- Read-outs and notes: bounded, the newest note on top, so the switcher below always stays on screen --}}
            <div id="readouts" @unless ($hasReadouts) hidden @endunless class="max-h-[10rem] shrink-0 space-y-1.5 overflow-y-auto border-b border-gray-200 p-3 max-sm:max-h-[8rem]">
                @yield('readouts')
                <ul id="notes" hidden class="m-0 list-none space-y-1 p-0 text-sm"></ul>
            </div>

            <div class="flex shrink-0 items-center gap-3 border-b border-gray-200 px-3 py-2">
                <div id="hood-sections" role="tablist" aria-label="Code, requests and stored data" class="flex gap-0.5 rounded-lg bg-gray-100 p-[3px]">
                    @foreach (['code' => 'Code', 'requests' => 'Requests', 'stored' => 'Stored'] as $key => $label)
                        <button type="button" role="tab" id="hood-tab-{{ $key }}" data-section="{{ $key }}" aria-controls="hood-{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" tabindex="{{ $loop->first ? 0 : -1 }}"
                                class="flex h-8 cursor-pointer items-center gap-1.5 rounded-md border-0 bg-transparent px-3 text-sm font-semibold text-gray-600 [font-family:inherit] hover:text-gray-900 aria-selected:bg-white aria-selected:text-gray-900 aria-selected:shadow-sm">
                            {{ $label }}
                            @if ($key === 'requests')
                                <span id="requests-count" class="font-medium text-gray-500"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>

            <section id="hood-code" role="tabpanel" aria-labelledby="hood-tab-code" tabindex="0" class="min-h-0 flex-1 overflow-y-auto p-4">
                <p class="mb-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                    <a class="text-sjs-dark underline" href="{{ $blob }}/{{ $step['files']['server'] ?? $step['files']['client'] }}">Source on GitHub</a>
                    @isset($step['files']['definition'])
                        <span>Definition: <a class="text-sjs-dark underline" href="{{ $blob }}/{{ $step['files']['definition'] }}"><code>{{ $step['files']['definition'] }}</code></a>@isset($step['files']['definitionV2']), <a class="text-sjs-dark underline" href="{{ $blob }}/{{ $step['files']['definitionV2'] }}"><code>{{ $step['files']['definitionV2'] }}</code></a>@endisset</span>
                    @endisset
                    @if ($commercial)
                        <span class="text-gray-500">{{ Str::before($step['licence'], ',') }} licence: without <code class="rounded bg-gray-100 px-1">SURVEYJS_LICENSE_KEY</code> the component shows a licence banner</span>
                    @endif
                </p>
                <div class="grid gap-4">
                    @foreach (\App\Support\Demo::codePanel($step) as $region)
                        <figure class="min-w-0 overflow-hidden rounded-lg border border-gray-800 bg-[#0d1117]">
                            <figcaption class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-800 px-4 py-2 text-xs text-gray-300">
                                <span>
                                    <span class="font-semibold text-white">{{ ucfirst($region['role']) }}</span> · <code>{{ $region['path'] }}</code> · <code>sjs:{{ $region['id'] }}</code>
                                    @if ($region['run'])
                                        · run <code class="text-white">{{ $region['run']['command'] }}</code> (port {{ $region['run']['port'] }})
                                    @endif
                                </span>
                                <a class="text-sjs underline" href="{{ $region['url'] }}">View on GitHub</a>
                            </figcaption>
                            <pre class="m-0 overflow-x-auto p-0 text-[12.5px] leading-relaxed"><code class="language-{{ $region['language'] }} block !bg-transparent p-4">{{ $region['code'] }}</code></pre>
                        </figure>
                    @endforeach
                </div>
            </section>

            <section id="hood-requests" role="tabpanel" aria-labelledby="hood-tab-requests" hidden class="flex min-h-0 flex-1 flex-col">
                <div class="flex shrink-0 items-center justify-end gap-3 border-b border-gray-100 px-4 py-1.5 text-xs text-gray-500">
                    <label class="flex items-center gap-1"><input id="show-demo-requests" type="checkbox"> show demo requests</label>
                    <button id="clear-requests" type="button" class="cursor-pointer border-0 bg-transparent p-0 text-xs text-gray-500 underline [font-family:inherit]">clear</button>
                </div>
                <p id="requests-empty" class="px-4 py-3 text-sm text-gray-400">No requests yet.</p>
                <ol id="requests" class="m-0 min-h-0 flex-1 list-none divide-y divide-gray-100 overflow-y-auto p-0 font-mono text-xs"></ol>
            </section>

            <section id="hood-stored" role="tabpanel" aria-labelledby="hood-tab-stored" hidden class="flex min-h-0 flex-1 flex-col">
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-gray-100 px-4 py-1.5 text-xs text-gray-500">
                    <span>What the server stored</span>
                    <span id="stored-updated" class="text-gray-400"></span>
                </div>
                <div id="stored" class="min-h-0 flex-1 space-y-3 overflow-y-auto p-4"></div>
            </section>
        </div>
    </div>
</main>

{{-- Rows that shared/client/host.js clones and fills by data-field. The two body slots switch on its body capture. --}}
<template id="request-row">
    <li class="data-[demo=true]:bg-gray-50 data-[demo=true]:text-gray-400 data-[state=error]:bg-red-50">
        <button type="button" data-row-toggle aria-expanded="false"
                class="group grid w-full cursor-pointer grid-cols-[0.875rem_3.5rem_minmax(0,1fr)_auto] items-center gap-x-2 border-0 bg-transparent px-4 py-1.5 text-left font-mono text-xs [color:inherit] hover:bg-black/[0.03]">
            <svg aria-hidden="true" class="size-3.5 transition-transform group-aria-expanded:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 6 15 12 9 18"/></svg>
            <span data-field="method" class="font-semibold"></span>
            <span data-field="url" class="truncate"></span>
            <span class="text-right"><span data-field="status" class="font-semibold"></span> <span data-field="time" class="text-gray-400"></span></span>
            <span data-field="headers" class="col-span-3 col-start-2 truncate text-[11px] text-sjs-dark empty:hidden"></span>
        </button>
        <div data-row-bodies hidden class="grid gap-3 pb-3 pl-10 pr-4 md:grid-cols-2">
            <div class="min-w-0">
                <p class="mb-1 font-sans text-[11px] font-bold uppercase tracking-wide text-gray-500">Request body</p>
                <pre data-field="request-body" class="m-0 max-h-80 overflow-auto rounded border border-gray-200 bg-white p-2 font-mono text-xs leading-snug text-gray-800 empty:before:text-gray-400 empty:before:content-['(none)']"></pre>
            </div>
            <div class="min-w-0">
                <p class="mb-1 font-sans text-[11px] font-bold uppercase tracking-wide text-gray-500">Response</p>
                <pre data-field="response-body" class="m-0 max-h-80 overflow-auto rounded border border-gray-200 bg-white p-2 font-mono text-xs leading-snug text-gray-800 empty:before:text-gray-400 empty:before:content-['(none)']"></pre>
            </div>
        </div>
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
