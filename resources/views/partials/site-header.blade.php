{{-- Demo only: the surveyjs.io header on the catalog, as in the Figma design (General, page "Backend Integration
     examples page + demo"). The menus are the site's own. At 1280px and up they open on hover and on keyboard
     focus; narrower, the Menu disclosure lists them. No JavaScript. --}}
@php
    $site = 'https://surveyjs.io';
    $href = fn (string $path) => str_starts_with($path, 'http') ? $path : $site.$path;
    $menus = [
        'Products' => [
            ['Form Library', '/form-library'],
            ['Survey Creator', '/json-form-builder'],
            ['Dashboard', '/dashboard'],
            ['PDF Generator', '/pdf-generator'],
            ['WordPress Plugin', 'https://wordpress.org/plugins/surveyjs/'],
            ['Partner Solutions', '/partner-solutions'],
            ['All-In-One Demo', '/free-survey-tool'],
            ['Features', '/features'],
        ],
        'Demos' => [
            ['Form Library', '/form-library/examples/overview'],
            ['Survey Creator', '/survey-creator/examples/free-nps-survey-template/reactjs'],
            ['Dashboard', '/dashboard/examples/interactive-survey-data-dashboard'],
            ['PDF Generator', '/pdf-generator/examples/save-completed-forms-as-pdf-files/reactjs'],
            ['All-in-One Demo', '/create-free-survey'],
            ['NodeJS / ASP.NET / PHP Servers', '/backend-integration/examples'],
            ['Bootstrap / Material UI / shadcn/ui', '/themes/theme-adapters'],
            ['Try Yourself', '/try'],
            ['Multi-User Form Editing', '/survey-creator/examples/collaborative-survey-creator'],
            ['Multi-User Form Filling', '/form-library/examples/collaborative-survey'],
            ['UI Preset Editor', '/survey-creator/examples/ui-preset-editor'],
            ['Apply a Theme to a PDF Form', '/pdf-generator/examples/apply-surveyjs-theme-to-pdf-form'],
            ['Paper Form Data Extraction', '/form-library/examples/extract-data-from-paper-forms-pdf'],
        ],
        'Developers' => [
            ['Documentation', '/documentation'],
            ['Setup Guides for My Stack', '/find-surveyjs-guides-for-my-stack'],
            ['FAQ', '/faq'],
            ['Support', '/support'],
            ['Source Code on GitHub', 'https://github.com/surveyjs'],
            ['Changelog', '/stay-updated/release-notes'],
            ['Licensing', '/licensing'],
        ],
        'Industries' => [
            ['Healthcare', '/healthcare'],
            ['Human Resources', '/human-resources'],
            ['Education', '/education'],
            ['Market Research', '/market-research'],
        ],
        'Stay Updated' => [
            ['Blog', '/stay-updated'],
            ['Release Notes', '/stay-updated/release-notes'],
            ['Breaking Changes', '/stay-updated/breaking-changes'],
            ['Major Updates', '/stay-updated/major-updates/2025-2026'],
            ['Roadmap', '/stay-updated/roadmap'],
            ['Case Studies', '/stay-updated/case-studies'],
            ['Tutorials', '/stay-updated/tutorials'],
            ['White Papers', '/stay-updated/white-papers'],
            ['Migration Guides', '/stay-updated/migration-guides'],
        ],
    ];
    $item = 'flex items-start gap-2 px-4 py-2 text-sm/4 font-semibold whitespace-nowrap text-sjs-fg';
@endphp
<header class="relative z-40 bg-white">
    <div class="flex items-center justify-between py-2 pr-2 pl-6 xl:pr-4 xl:pl-8">
        <a href="{{ $site }}" class="shrink-0">
            <img src="{{ asset('images/site/logo-small.svg') }}" width="78.1377" height="32" alt="SurveyJS" class="mt-3 mb-1 md:hidden">
            <img src="{{ asset('images/site/logo-medium.svg') }}" width="122.09" height="50" alt="SurveyJS" class="mt-3.5 hidden md:block xl:hidden">
            <img src="{{ asset('images/site/logo-large.svg') }}" width="161.159" height="66" alt="SurveyJS" class="mt-5 mb-0.5 hidden xl:block">
        </a>

        <nav aria-label="surveyjs.io" class="flex items-start gap-1 md:gap-0">
            <ul class="m-0 hidden list-none items-start p-0 xl:flex">
                @foreach ($menus as $title => $links)
                    <li class="group relative">
                        <button type="button" aria-haspopup="true" class="{{ $item }} cursor-pointer gap-1 border-0 bg-transparent font-sans">
                            {{ $title }}
                            <img src="{{ asset('images/site/chevron-down.svg') }}" width="12" height="12" alt="" class="mt-0.5">
                        </button>
                        <ul class="invisible absolute top-full left-0 m-0 min-w-56 list-none rounded-lg border border-sjs-line bg-white py-2 shadow-lg group-focus-within:visible group-hover:visible">
                            @foreach ($links as [$label, $path])
                                <li><a href="{{ $href($path) }}" class="{{ $item }} no-underline hover:bg-sjs-back-gray">{{ $label }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
                <li><a href="{{ $site }}/pricing" class="{{ $item }} no-underline">Pricing</a></li>
                <li aria-hidden="true" class="px-2"><span class="block h-8 w-px bg-sjs-line"></span></li>
            </ul>

            <a href="https://github.com/surveyjs/survey-library" class="{{ $item }} no-underline" aria-label="SurveyJS on GitHub">
                <img src="{{ asset('images/site/github.svg') }}" width="16" height="16" alt="">
                <span class="hidden md:inline">GitHub</span>
                <img src="{{ asset('images/site/external-link.svg') }}" width="12" height="12" alt="" class="mt-0.5 hidden md:block">
            </a>
            <span aria-hidden="true" class="px-2"><span class="block h-8 w-px bg-sjs-line"></span></span>
            <a href="{{ $site }}/cart" class="{{ $item }} no-underline" aria-label="Cart">
                <img src="{{ asset('images/site/cart.svg') }}" width="16" height="16" alt="">
                <span class="hidden md:inline">Cart</span>
            </a>
            <a href="{{ $site }}/login" class="{{ $item }} no-underline" aria-label="Log In">
                <img src="{{ asset('images/site/user.svg') }}" width="16" height="16" alt="">
                <span class="hidden md:inline">Log In</span>
            </a>

            <details class="xl:hidden">
                <summary class="{{ $item }} cursor-pointer list-none [&::-webkit-details-marker]:hidden" aria-label="Menu">
                    <img src="{{ asset('images/site/menu.svg') }}" width="16" height="16" alt="">
                    <span class="hidden md:inline">Menu</span>
                </summary>
                <div class="absolute inset-x-0 top-full max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-sjs-line bg-white px-6 pt-4 pb-6 shadow-lg">
                    <div class="grid gap-6 sm:grid-cols-2 md:grid-cols-3">
                        @foreach ($menus as $title => $links)
                            <section>
                                <h2 class="px-4 pb-1 text-sm/4 font-semibold text-sjs-fg-50">{{ $title }}</h2>
                                <ul class="m-0 list-none p-0">
                                    @foreach ($links as [$label, $path])
                                        <li><a href="{{ $href($path) }}" class="{{ $item }} whitespace-normal no-underline">{{ $label }}</a></li>
                                    @endforeach
                                </ul>
                            </section>
                        @endforeach
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <a href="{{ $site }}/pricing" class="{{ $item }} no-underline">Pricing</a>
                        <a href="{{ $site }}/create-free-survey" class="rounded-lg bg-sjs-yellow px-3 py-2 text-sm/4 font-semibold text-white no-underline">See in Action</a>
                    </div>
                </div>
            </details>

            <a href="{{ $site }}/create-free-survey" class="mx-4 hidden rounded-lg bg-sjs-yellow px-3 py-2 text-sm/4 font-semibold whitespace-nowrap text-white no-underline xl:block">See in Action</a>
        </nav>
    </div>
</header>
