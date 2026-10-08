{{-- Demo only: the catalog, rendered from surveyjs-integration.json --}}
@php
    $sections = [
        'I' => ['Run forms', 'The Form Library renders forms in the browser. Your backend needs exactly one thing: an endpoint that stores the response. Everything after the first item is optional.'],
        'II' => ['See results in a dashboard', 'SurveyJS Dashboard runs in the browser. Your backend returns the stored responses.'],
        'III' => ['Edit forms in Survey Creator', 'For your backend, a definition is one more JSON document to load and save. Saving definitions is an admin action: authorize it.'],
        'IV' => ['Run SurveyJS on your server', 'Only if you can\'t trust the client: validate, lint, PDF, extraction, through the SurveyJS service next to this app.'],
    ];
    $steps = collect($manifest['steps'])->groupBy(fn ($step, $id) => explode('.', $id)[0], preserveKeys: true);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SurveyJS Server Integration · PHP / Laravel</title>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased">
<header class="border-b border-gray-200 bg-white">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <p class="flex items-center gap-2 text-sm">
            <span class="rounded bg-sjs px-1.5 py-0.5 font-bold text-white">SurveyJS</span>
            <span class="text-gray-500">surveyjs-php · Laravel {{ app()->version() }} · SurveyJS {{ $manifest['surveyjsVersion'] }}</span>
        </p>
        <h1 class="mt-4 text-3xl font-bold text-gray-900 sm:text-4xl">Server Integration, step by step, in PHP</h1>
        <p class="mt-4 max-w-3xl text-lg text-gray-600">
            The code behind <a class="text-sjs-dark underline" href="https://surveyjs.io/backend-integration/examples">Integrate SurveyJS with your backend</a>,
            as a Laravel application. Each step runs the real SurveyJS component against these routes and shows the code that just ran.
            SurveyJS has no backend of its own, so treat these as starting points.
        </p>
        <p class="mt-3 text-sm text-gray-500">
            Commercial steps (Dashboard, Creator, server-side PDF) run without a licence key and show a licence banner;
            set <code class="rounded bg-gray-100 px-1">SURVEYJS_LICENSE_KEY</code> to remove it.
            <a class="text-sjs-dark underline" href="{{ config('surveyjs.github') }}">Source on GitHub</a>
        </p>
    </div>
</header>

<main class="mx-auto max-w-6xl space-y-12 px-4 py-10 sm:px-6">
    @foreach ($sections as $number => [$title, $intro])
        @continue(! $steps->has($number))
        <section>
            <div class="flex items-center gap-3">
                <span class="flex size-9 items-center justify-center rounded-full bg-gray-900 text-sm font-bold text-white">{{ $number }}</span>
                <h2 class="text-2xl font-bold text-gray-900">{{ $title }}</h2>
            </div>
            <p class="mt-2 max-w-3xl text-gray-600">{{ $intro }}</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach ($steps[$number] as $id => $step)
                    <article class="flex flex-col rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-mono text-sm font-semibold text-gray-400">{{ $id }}</span>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $step['title'] }}</h3>
                            <span @class([
                                'rounded px-1.5 py-0.5 text-[11px] font-bold',
                                'bg-blue-50 text-blue-700' => str_starts_with($step['licence'], 'MIT'),
                                'bg-emerald-50 text-emerald-700' => ! str_starts_with($step['licence'], 'MIT'),
                            ])>{{ $step['licence'] }}</span>
                        </div>
                        <p class="mt-2 flex-1 text-sm text-gray-600"><strong class="text-gray-800">You need this:</strong> {{ $step['need'] }}</p>
                        <p class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm">
                            <a class="font-semibold text-sjs-dark underline" href="{{ url($step['page']) }}">Run</a>
                            <a class="text-sjs-dark underline" href="{{ config('surveyjs.github') }}/blob/{{ $manifest['branch'] }}/{{ $step['files']['server'] ?? $step['files']['client'] }}">Source</a>
                            <a class="text-sjs-dark underline" href="https://surveyjs.io/backend-integration/examples#{{ $step['anchor'] }}">On the page</a>
                        </p>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach
</main>

<footer class="border-t border-gray-200 py-8 text-center text-sm text-gray-500">
    Also on <a class="text-sjs-dark underline" href="https://github.com/surveyjs/surveyjs-nodejs">Node.js</a>,
    <a class="text-sjs-dark underline" href="https://github.com/surveyjs/surveyjs-aspnet-mvc">ASP.NET Core</a>,
    <a class="text-sjs-dark underline" href="https://github.com/surveyjs/surveyjs-python-flask">Python</a> and
    <a class="text-sjs-dark underline" href="https://github.com/surveyjs/surveyjs-nextjs">Next.js</a> ·
    <a class="text-sjs-dark underline" href="https://app.demos.surveyjs.io">SurveyJS demos</a>
</footer>
</body>
</html>
