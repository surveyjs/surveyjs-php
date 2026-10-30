{{-- Demo only: the catalog, rendered from surveyjs-integration.json, in the SurveyJS site's design
     (Figma General, page "Backend Integration examples page + demo"; desktop 1400, tablet 900, mobile 375) --}}
@php
    $sections = [
        'Run Forms' => 'SurveyJS Form Library displays a form using a JSON schema and collects the respondent\'s answers. Start integration by adding an endpoint that saves those answers. You can then load data from the server, save progress in unfinished forms, and add the other features described below if your application needs them.',
        'Analyze Results with Dashboard' => 'Once you have collected responses, use SurveyJS Dashboard to display and analyze them. Dashboard runs in the browser and uses the form JSON schema to choose a chart for each question automatically.',
        'Edit Forms in Survey Creator' => 'Survey Creator lets authors build and edit forms in your application. It works with the same form JSON schemas that Form Library displays to respondents. Your backend should load those schemas when the editor opens and save them when an author makes changes.',
        'Run Server-Side Workflows' => 'Some workflows need SurveyJS logic on the server. You may need to check a submission before storing it, find problems in a form JSON schema, create a PDF, or read answers from a document. These operations run in Node.js. A Node.js backend can run the components directly; other backends can call a Node.js service over HTTP.',
    ];
    $steps = collect($manifest['steps'])->groupBy('section');
    // The manifest's licence reads "MIT", "Commercial" or "Commercial client", optionally followed by ", needs an AI provider"
    $licence = fn (array $step) => str_starts_with($step['licence'], 'MIT') ? 'MIT' : 'Commercial';
    $needsAi = fn (array $step) => str_contains($step['licence'], 'needs an AI provider');
    $cardBadge = 'rounded-lg px-3 py-1 text-base/6 font-semibold';
    $licenceBadge = ['MIT' => 'bg-sjs-10 text-sjs', 'Commercial' => 'bg-sjs-yellow-10 text-sjs-yellow'];
    // Site Button, medium: primary and tertiary neutral
    $blackButton = 'rounded-lg bg-sjs-fg px-6 py-3 text-center text-base/6 font-semibold text-white no-underline hover:bg-gray-700';
    $whiteButton = 'inline-block rounded-lg bg-white px-6 py-3 text-center text-base/6 font-semibold text-sjs-fg no-underline shadow-[inset_0_0_0_1px_var(--color-sjs-border)] hover:bg-sjs-back-gray';
    // Site Button, brand: large on tablets and desktops, medium on phones
    $heroButton = 'rounded-lg px-6 py-3 text-center text-base/6 font-semibold no-underline md:px-8 md:py-4 md:text-xl/6';
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
        <h1 class="mx-auto max-w-[880px] text-sjs-title-lg font-bold text-balance md:text-sjs-title-2xl">Build a PHP Backend for Your SurveyJS Forms</h1>
        <p class="mt-6 mb-0 text-sjs-para-lg font-medium text-pretty text-sjs-fg-50">
            The code behind <a class="text-inherit underline decoration-dotted [text-underline-position:from-font] hover:text-sjs-fg" href="{{ \App\Support\Demo::docsUrl() }}" target="_blank">Integrate SurveyJS with PHP</a>,
            as a Laravel application. Each step runs the real SurveyJS component against these routes and shows the code that just ran.
            SurveyJS has no backend of its own, so treat these as starting points.
        </p>
    </div>

    {{-- Phones: stacked, full-width buttons --}}
    <div class="{{ $column }} pb-12 md:pb-16 xl:pb-24">
        <p class="m-0 mt-2.5 flex flex-col gap-3 md:mt-0 md:flex-row md:justify-center md:gap-8">
            <a class="{{ $heroButton }} bg-sjs text-white hover:bg-sjs-dim" href="https://app.demos.surveyjs.io" target="_blank">View examples</a>
            <a class="{{ $heroButton }} bg-sjs-15 text-sjs-fg hover:bg-sjs-15-dim" href="{{ config('surveyjs.github') }}" target="_blank">View on GitHub</a>
        </p>
    </div>

    @foreach ($sections as $title => $intro)
        @continue(! $steps->has($title))
        <section @class(['border-t border-sjs-line pb-12 md:pb-16 xl:pb-24', 'bg-sjs-back-gray' => $number++ % 2 === 0])>
            <div class="{{ $column }} pt-16 pb-12 xl:pt-24">
                <h2 class="text-sjs-title-lg font-bold md:text-sjs-title-xl">{{ $title }}</h2>
                <p class="mt-3 mb-0 text-sjs-para-lg font-medium text-sjs-fg-50">{{ $intro }}</p>
            </div>
            <div class="{{ $column }} grid gap-3 md:grid-cols-2">
                @foreach ($steps[$title] as $step)
                    {{-- A card alone in its row takes the full width. Phones: stacked, full-width buttons --}}
                    <article class="flex flex-col gap-10 rounded-3xl border border-sjs-line bg-white p-4 md:p-6 md:odd:last:col-span-2">
                        <div class="flex flex-1 flex-col gap-6 p-2 md:p-0">
                            <div class="flex flex-wrap gap-3">
                                <span class="{{ $cardBadge }} {{ $licenceBadge[$licence($step)] }}">{{ $licence($step) }}</span>
                                @if ($needsAi($step))
                                    <span class="{{ $cardBadge }} bg-sjs-violet-10 text-sjs-violet">Needs an AI provider</span>
                                @endif
                            </div>
                            <div class="flex flex-col gap-3">
                                <h3 class="text-sjs-para-lg font-semibold">{{ $step['title'] }}</h3>
                                <p class="m-0 text-sjs-para-md text-sjs-fg-75">{{ $step['need'] }}</p>
                            </div>
                        </div>
                        <p class="m-0 flex flex-col gap-3 md:flex-row md:flex-wrap md:items-center">
                            <a class="{{ $blackButton }}" href="{{ url($step['page']) }}">Run</a>
                            <a class="{{ $whiteButton }}" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['server'] ?? $step['files']['client'] }}" target="_blank">Source ↗&#xFE0E;</a>
                            <a class="{{ $whiteButton }}" href="{{ \App\Support\Demo::docsUrl($step) }}" target="_blank">On the page ↗&#xFE0E;</a>
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
                    Also on <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-nodejs" target="_blank">Node.js ↗&#xFE0E;</a>,
                    <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-aspnet-mvc" target="_blank">ASP.NET Core ↗&#xFE0E;</a>,
                    <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-python-flask" target="_blank">Python ↗&#xFE0E;</a> and
                    <a class="text-inherit underline decoration-dotted [text-underline-position:from-font]" href="https://github.com/surveyjs/surveyjs-nextjs" target="_blank">Next.js ↗&#xFE0E;</a>
                </p>
                <a class="{{ $whiteButton }}" href="https://app.demos.surveyjs.io" target="_blank">See SurveyJS Demos</a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
