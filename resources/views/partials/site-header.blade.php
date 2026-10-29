{{-- Demo only: the catalog header. The SurveyJS logo, linking to surveyjs.io, and the app title. The logo's tail
     (the bottom quarter of each image) hangs below the row, so the title centres on the logo's box. --}}
<header class="bg-white">
    <div class="flex items-center gap-4 px-6 pt-5 pb-7 md:pb-8 xl:px-8 xl:pt-6 xl:pb-10">
        <a href="https://surveyjs.io" class="shrink-0">
            <img src="{{ asset('images/site/logo-small.svg') }}" width="78.1377" height="32" alt="SurveyJS" class="-mb-2 block md:hidden">
            <img src="{{ asset('images/site/logo-medium.svg') }}" width="122.09" height="50" alt="SurveyJS" class="-mb-3 hidden md:block xl:hidden">
            <img src="{{ asset('images/site/logo-large.svg') }}" width="161.159" height="66" alt="SurveyJS" class="-mb-4 hidden xl:block">
        </a>
        <span aria-hidden="true" class="block h-8 w-px shrink-0 bg-sjs-line"></span>
        <span class="text-sm/5 font-semibold text-sjs-fg md:text-base/6 xl:text-xl/7">{{ \App\Support\Demo::TITLE }}</span>
    </div>
</header>
