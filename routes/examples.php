<?php

declare(strict_types=1);

// One file per step of the Server Integration page, in page order. Each file holds only that
// step's routes; copy the one you need. They run in the stateless "api" group (bootstrap/app.php).
// I.8 and III.5 are WebSocket relays, run as their own processes: app/Relay, `php artisan relay:fill|edit`.

// Section I — Run forms
require __DIR__.(config('surveyjs.validate_responses')
    ? '/examples/validate-response.php'         // IV.1: POST /api/responses, validated by the SurveyJS service
    : '/examples/save-response.php');           // I.1:  POST /api/responses
require __DIR__.'/examples/load-definition-variables-data.php';   // I.2: GET /claim, GET /api/forms/{id}
require __DIR__.'/examples/resume-progress.php';                  // I.3: GET|PUT /api/progress/{formId}
require __DIR__.'/examples/store-files.php';                      // I.4: POST /api/files, GET /api/files/{id}
require __DIR__.'/examples/choices-from-web.php';                 // I.5: GET /api/offices, GET /api/countries
require __DIR__.'/examples/async-functions.php';                  // I.6: GET /api/customers/exists, GET /api/shipping
require __DIR__.'/examples/relational-storage.php';               // I.7: POST /api/claims

// Section II — See results in a dashboard
require __DIR__.'/examples/dashboard.php';                        // II:  GET /api/responses

// Section III — Edit forms in Survey Creator (III.2 reuses POST /api/files from I.4)
require __DIR__.(config('surveyjs.lint_definitions')
    ? '/examples/lint-definition.php'           // IV.2: PUT /api/forms/{id}, linted by the SurveyJS service
    : '/examples/creator-load-save.php');       // III.1: PUT /api/forms/{id}
require __DIR__.'/examples/ai-translation.php';                   // III.3: POST /api/translate
require __DIR__.'/examples/variable-presets.php';                 // III.4: GET|PUT /api/variable-presets/{formId}

// Section IV — Run SurveyJS on your server (IV.1 and IV.2 are switched in above)
require __DIR__.'/examples/server-pdf.php';                       // IV.3: GET /api/claims/{id}/pdf
require __DIR__.'/examples/extract-from-paper.php';               // IV.4: POST /api/work-orders/extract
