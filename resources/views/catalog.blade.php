{{-- Demo only: the catalog, rendered from surveyjs-integration.json, in the SurveyJS site's design
     (Figma General, page "Backend Integration examples page + demo"; desktop 1400, tablet 900, mobile 375) --}}
@php
    $sections = [
        '1' => ['Run forms', 'The Form Library renders forms in the browser. Your backend needs exactly one thing: an endpoint that stores the response. Everything after the first item is optional — add it when a form needs it.'],
        '2' => ['See results in a dashboard', 'SurveyJS Dashboard runs in the browser. Your backend returns the stored responses.'],
        '3' => ['Edit forms in Survey Creator', 'For your backend, a definition is one more JSON document to load and save. Saving definitions is an admin action: authorize it.'],
        '4' => ['Run SurveyJS on your server', 'Only if you can\'t trust the client: validate, lint, PDF, extraction, through the SurveyJS service next to this app.'],
    ];
    $steps = collect($manifest['steps'])->groupBy(fn ($step, $id) => explode('.', $id)[0], preserveKeys: true);
    // The manifest's licence reads "MIT", "Commercial" or "Commercial client", optionally followed by ", needs an AI provider"
    $licence = fn (array $step) => str_starts_with($step['licence'], 'MIT') ? 'MIT' : 'Commercial';
    $needsAi = fn (array $step) => str_contains($step['licence'], 'needs an AI provider');
    // Section badges: 20px on white, the tint of the licence colour; on a gray section, white
    $sectionBadge = 'rounded-lg px-4 py-3 text-xl/6 font-semibold';
    $cardBadge = 'rounded-lg px-3 py-1 text-base/6 font-semibold';
    $badgeText = ['MIT' => 'text-sjs-blue', 'Commercial' => 'text-sjs'];
    $badgeTint = ['MIT' => 'bg-sjs-blue-10', 'Commercial' => 'bg-sjs-10'];
    $siteLink = 'text-base/6 font-semibold text-sjs-blue underline decoration-dotted [text-underline-position:from-font] hover:decoration-solid';
    $whiteButton = 'inline-block rounded-lg bg-white px-4 py-3 text-sm/4 font-semibold text-sjs-fg no-underline shadow-[inset_0_0_0_1px_var(--color-sjs-border)] hover:bg-sjs-back-gray';
    $column = 'mx-auto max-w-sjs-content px-4 md:px-6';
    $number = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ \App\Support\Demo::TITLE }} · SurveyJS</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-white font-sans text-sjs-fg antialiased">
@include('partials.site-header')

<main>
    <div class="{{ $column }} pt-12 pb-12 text-center md:pt-16 xl:pt-20">
        <h1 class="mx-auto max-w-[880px] text-sjs-title-lg font-bold text-balance md:text-sjs-title-2xl">Add a backend to your SurveyJS forms</h1>
        <p class="mt-6 mb-0 text-sjs-para-lg font-medium text-pretty text-sjs-fg-50">
            The code behind <a class="text-inherit underline decoration-dotted [text-underline-position:from-font] hover:text-sjs-fg" href="{{ \App\Support\Demo::docsUrl() }}">Integrate SurveyJS with your backend</a>,
            as a Laravel application. Each step runs the real SurveyJS component against these routes and shows the code that just ran.
            SurveyJS has no backend of its own, so treat these as starting points.
        </p>
    </div>

    <div class="{{ $column }} pb-12 md:pb-16 xl:pb-24">
        <div class="flex flex-col items-center gap-6 rounded-3xl bg-sjs-back-gray px-10 py-8 text-center">
            <p class="m-0 text-sjs-para-md">
                Commercial steps (Dashboard, Creator, server-side PDF) run without a licence key and show a licence banner;
                set <code class="font-sans">SURVEYJS_LICENSE_KEY</code> to remove it.
            </p>
            <a class="{{ $whiteButton }}" href="{{ config('surveyjs.github') }}">Source on GitHub</a>
        </div>
    </div>

    @foreach ($sections as $id => [$title, $intro])
        @continue(! $steps->has($id))
        @php
            $gray = $number++ % 2 === 0;
            $licences = $steps[$id]->map($licence)->unique()->values();
        @endphp
        <section @class(['border-t border-sjs-line pb-12 md:pb-16 xl:pb-24', 'bg-sjs-back-gray' => $gray])>
            <div class="{{ $column }} pt-16 pb-12 xl:pt-24">
                <div class="flex flex-wrap items-center gap-6">
                    <h2 class="text-sjs-title-lg font-bold md:text-sjs-title-xl">{{ $title }}</h2>
                    <div class="flex gap-3">
                        @foreach ($licences as $kind)
                            <span class="{{ $sectionBadge }} {{ $badgeText[$kind] }} {{ $gray ? 'bg-white' : $badgeTint[$kind] }}">{{ $kind }}</span>
                        @endforeach
                    </div>
                </div>
                <p class="mt-3 mb-0 text-sjs-para-lg font-medium text-sjs-fg-50">{{ $intro }}</p>
            </div>
            <div class="{{ $column }} grid gap-3 md:grid-cols-2">
                @foreach ($steps[$id] as $stepId => $step)
                    <article class="flex flex-col gap-10 rounded-3xl border border-sjs-line bg-white p-6">
                        <div class="flex flex-1 flex-col gap-3">
                            <div class="flex flex-wrap gap-3">
                                <span class="{{ $cardBadge }} bg-sjs-back-gray text-sjs-fg-75">{{ $stepId }}</span>
                                @if ($licences->count() > 1)
                                    <span class="{{ $cardBadge }} {{ $badgeText[$licence($step)] }} {{ $badgeTint[$licence($step)] }}">{{ $licence($step) }}</span>
                                @endif
                                @if ($needsAi($step))
                                    <span class="{{ $cardBadge }} bg-sjs-yellow-10 text-sjs-yellow">Needs an AI provider</span>
                                @endif
                            </div>
                            <h3 class="text-sjs-para-lg font-semibold">{{ $step['title'] }}</h3>
                            <p class="m-0 text-sjs-para-md text-sjs-fg-75"><strong class="font-bold">You need this:</strong> {{ $step['need'] }}</p>
                        </div>
                        <p class="m-0 flex flex-wrap items-center gap-x-5 gap-y-2">
                            <a class="rounded-lg bg-sjs-back-gray-2 px-6 py-3 text-base/6 font-semibold text-sjs-fg no-underline hover:bg-gray-200" href="{{ url($step['page']) }}">Run</a>
                            <a class="{{ $siteLink }}" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['server'] ?? $step['files']['client'] }}">Source ↗&#xFE0E;</a>
                            <a class="{{ $siteLink }}" href="{{ \App\Support\Demo::docsUrl($step) }}">On the page ↗&#xFE0E;</a>
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    <div class="border-t border-sjs-line">
        <div class="{{ $column }} py-12 md:py-16 xl:py-24">
            <div class="flex flex-col items-center gap-6 rounded-3xl bg-sjs-back-gray px-10 py-8 text-center">
                <p class="m-0 text-sjs-para-md">
                    Also on <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-nodejs">Node.js ↗&#xFE0E;</a>,
                    <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-aspnet-mvc">ASP.NET Core ↗&#xFE0E;</a>,
                    <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-python-flask">Python ↗&#xFE0E;</a> and
                    <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-nextjs">Next.js ↗&#xFE0E;</a>
                </p>
                <a class="{{ $whiteButton }}" href="https://app.demos.surveyjs.io">See SurveyJS Demos</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
