{{-- Demo only: the surveyjs.io footer on the catalog, as in the Figma design. The links are the site's own;
     an external one (another site, or a file) shows the ↗ icon. --}}
@php
    $site = 'https://surveyjs.io';
    $href = fn (string $path) => str_starts_with($path, 'http') ? $path : $site.$path;
    $products = [
        ['Form Library', '/form-library'],
        ['Survey Creator', '/json-form-builder'],
        ['Dashboard', '/dashboard'],
        ['PDF Generator', '/pdf-generator'],
        ['WordPress Plugin', 'https://wordpress.org/plugins/surveyjs/'],
    ];
    $groups = [
        'By Technology' => [
            ['React Form Builder', '/react-form-builder'],
            ['Angular Form Builder', '/angular-form-builder'],
            ['Vue.js Form Builder', '/vue-form-builder'],
            ['Vanilla JavaScript', '/try/vanillajs'],
        ],
        'Customization' => [
            ['Add Custom Input Fields', '/form-library/documentation/customize-question-types/question-customization-options'],
            ['Add Custom Validation', '/form-library/documentation/data-validation'],
            ['Style Forms with Custom CSS', '/documentation/design-tokens-css-customization'],
            ['White-Label the Survey Creator UI', '/documentation/themes-and-custom-styles'],
            ['Create Multilingual Forms', '/form-library/documentation/survey-localization'],
            ['Localise the Survey Creator UI', '/survey-creator/documentation/survey-localization-translate-surveys-to-different-languages'],
        ],
        'Backend Integration' => [
            ['Backend Integration Guide', '/documentation/backend-integration'],
            ['PHP Form Builder', '/php-form-builder'],
            ['ASP.NET Core Form Builder', '/asp-net-form-builder'],
            ['SurveyJS for Node.js', 'https://github.com/surveyjs/surveyjs-nodejs'],
            ['Node.js + PostgreSQL', 'https://github.com/surveyjs/surveyjs-nodejs-postgresql'],
            ['Node.js + MongoDB', 'https://github.com/surveyjs/surveyjs-nodejs-mongodb'],
            ['SurveyJS for Python', 'https://github.com/surveyjs/surveyjs-python-flask'],
        ],
        'Resources' => [
            ['Support', '/support'],
            ['FAQ', '/faq'],
            ['Documentation', '/documentation'],
            ['Release Notes', '/stay-updated/release-notes'],
            ['Major Updates', '/stay-updated/major-updates/2025-2026'],
            ['Pricing', '/pricing'],
            ['Licensing', '/licensing'],
        ],
    ];
    $company = [
        ['Contact Us', '/contact-us'],
        ['Careers', '/careers'],
        ['Leave a Review', 'https://reviews.capterra.com/products/new/93805969-80c2-4a34-bbf6-89e72208dc4f/b1e4407b-9c12-44ac-b0de-1b74077b0838?lang=en'],
        ['Partners', '/partner-solutions'],
        ['End User License Agreement', 'https://surveyjs.io/Developer-License-Agreement.pdf'],
        ['Terms of Use & Privacy Statement', '/terms-use'],
        ['Accessibility Statement', '/accessibility-statement'],
        ['Software Development Lifecycle (SDLC) Policy', '/software-development-lifecycle-policy'],
        ['Vulnerability Management Policy', '/vulnerability-management-policy'],
    ];
    $social = [
        ['Facebook', 'https://www.facebook.com/SurveyJS/', 'social-facebook.svg'],
        ['GitHub', 'https://github.com/surveyjs', 'social-github.svg'],
        ['Medium', 'https://medium.com/@surveyjs', 'social-medium.svg'],
        ['X', 'https://twitter.com/surveyjs', 'social-x.svg'],
    ];
    $card = 'flex flex-col gap-4 rounded-2xl bg-white/6 p-5';
    $title = 'm-0 text-sm/4 font-semibold text-sjs-on-dark-50';
    // One footer link; the ↗ icon marks a link that leaves surveyjs.io
    $link = fn (string $label, string $path) => '<a href="'.e($href($path)).'" class="inline-flex items-start gap-1 text-sm/4 font-semibold text-sjs-on-dark no-underline hover:underline">'.e($label)
        .(str_starts_with($path, 'http') ? '<img src="'.e(asset('images/site/external-link-dark-bg.svg')).'" width="12" height="12" alt="" class="mt-0.5 shrink-0">' : '').'</a>';
@endphp
<footer class="bg-gray-900 px-4 sm:px-6 xl:px-12">
    <div class="flex flex-col gap-6 py-10">
        <p class="m-0 max-w-[33rem] text-sm/6 font-semibold text-sjs-on-dark-50">
            SurveyJS is a JavaScript library suite that integrates directly into your app (React/Angular/Vue3). Install via npm and quickly build a custom form
        </p>
        <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
            <h2 class="text-sm/4 font-semibold text-sjs-on-dark-50">Products</h2>
            <ul class="m-0 flex list-none flex-wrap gap-x-4 gap-y-3 p-0">
                @foreach ($products as [$label, $path])
                    <li>{!! $link($label, $path) !!}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="flex flex-col gap-4">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($groups as $group => $links)
                <section class="{{ $card }}">
                    <h2 class="{{ $title }}">{{ $group }}</h2>
                    <ul class="m-0 flex list-none flex-col gap-4 p-0">
                        @foreach ($links as [$label, $path])
                            <li>{!! $link($label, $path) !!}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
        <section class="{{ $card }}">
            <h2 class="{{ $title }}">Company</h2>
            <ul class="m-0 flex list-none flex-col gap-4 p-0">
                @foreach ($company as [$label, $path])
                    <li>{!! $link($label, $path) !!}</li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="flex flex-wrap items-center gap-x-12 gap-y-6 py-6">
        <p class="m-0 min-w-48 flex-1 py-8 text-sm/6 font-semibold text-sjs-on-dark-50">Copyright © {{ now()->year }} Devsoft Baltic OÜ. All rights reserved</p>
        <div class="flex items-center gap-6">
            <span class="text-sm/4 font-semibold text-sjs-on-dark-50">Follow Us</span>
            @foreach ($social as [$name, $url, $icon])
                <a href="{{ $url }}" aria-label="SurveyJS on {{ $name }}" class="opacity-50 hover:opacity-80">
                    <img src="{{ asset('images/site/'.$icon) }}" width="32" height="32" alt="" class="block">
                </a>
            @endforeach
        </div>
        <div class="flex h-22 items-center gap-6">
            <a href="https://www.producthunt.com/posts/surveyjs-2" aria-label="SurveyJS on Product Hunt">
                <img src="{{ asset('images/site/badge-product-hunt.png') }}" width="184" height="40" alt="" class="block object-contain">
            </a>
            <a href="https://sourceforge.net/software/product/SurveyJS/" aria-label="SurveyJS reviews on SourceForge">
                <img src="{{ asset('images/site/badge-sourceforge.png') }}" width="52" height="59" alt="" class="block object-contain">
            </a>
        </div>
    </div>
</footer>
