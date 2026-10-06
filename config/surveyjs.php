<?php

declare(strict_types=1);

// Every SurveyJS and demo setting. Code reads config('surveyjs.…'), never env(),
// because env() returns null once the configuration is cached.
return [

    // Shown in the browser: setLicenseKey() runs only when it is set
    'license_key' => env('SURVEYJS_LICENSE_KEY'),

    // Section IV: SurveyJS Server (github.com/surveyjs/surveyjs-server), run next to the app
    'service_url' => rtrim((string) env('SURVEYJS_SERVICE_URL', 'http://localhost:3010'), '/'),
    'validate_responses' => (bool) env('VALIDATE_RESPONSES', false),
    'lint_definitions' => (bool) env('LINT_DEFINITIONS', false),

    // III.3: an OpenAI-compatible /chat/completions endpoint for machine translation
    'ai' => [
        'base_url' => rtrim((string) env('AI_BASE_URL', 'https://api.openai.com/v1'), '/'),
        'api_key' => env('AI_API_KEY'),
        'model' => env('AI_MODEL', 'gpt-4o-mini'),
    ],

    // IV.4: read only to pass them to the SurveyJS service in Docker Compose
    'extraction' => [
        'provider' => env('SURVEYJS_AI_PROVIDER'),
        'model' => env('SURVEYJS_AI_MODEL'),
        'ollama_base_url' => env('SURVEYJS_OLLAMA_BASE_URL'),
        'openai_api_key' => env('OPENAI_API_KEY'),
        'anthropic_api_key' => env('ANTHROPIC_API_KEY'),
    ],

    // I.8 and III.5: the WebSocket relays run as their own processes
    'relay' => [
        'fill_port' => (int) env('RELAY_PORT', 8081),
        'edit_port' => (int) env('EDIT_RELAY_PORT', 8082),
        'fill_url' => env('RELAY_URL'),         // null: the page's host on fill_port
        'edit_url' => env('EDIT_RELAY_URL'),    // null: the page's host on edit_port
    ],

    // Demo only: a private SQLite copy and uploads folder per visitor (see README)
    'demo_mode' => (bool) env('DEMO_MODE', false),
    'demo' => [
        'seed_database' => env('DB_DATABASE', database_path('database.sqlite')),
        'sandbox_path' => storage_path('app/sandbox'),
        'sandbox_ttl_hours' => 24,
        'max_upload_kb' => 5 * 1024,
        'ai_calls_per_day' => 20,
    ],

    'github' => 'https://github.com/surveyjs/surveyjs-php',
];
