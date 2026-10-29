<?php

declare(strict_types=1);

// One file per step of the Server Integration page, in page order. Each file holds only that
// step's routes; copy the one you need. They run in the stateless "api" group (bootstrap/app.php).
// 1.8 and 3.5 are WebSocket relays, run as their own processes: app/Relay, `php artisan relay:fill|edit`.

// Section 1 — Run forms
require __DIR__.(config('surveyjs.validate_responses')
    ? '/examples/validate-response.php'         // 4.1: POST /api/responses, validated by the SurveyJS service
    : '/examples/save-response.php');           // 1.1:  POST /api/responses
require __DIR__.'/examples/load-definition-variables-data.php';   // 1.2: GET /claim, GET /api/forms/{id}
require __DIR__.'/examples/resume-progress.php';                  // 1.3: GET|PUT /api/progress/{formId}
require __DIR__.'/examples/store-files.php';                      // 1.4: POST /api/files, GET /api/files/{id}
require __DIR__.'/examples/choices-from-web.php';                 // 1.5: GET /api/offices, GET /api/countries
require __DIR__.'/examples/async-functions.php';                  // 1.6: GET /api/customers/exists, GET /api/shipping
require __DIR__.'/examples/relational-storage.php';               // 1.7: POST /api/claims

// Section 2 — See results in a dashboard
require __DIR__.'/examples/dashboard.php';                        // 2:  GET /api/responses

// Section 3 — Edit forms in Survey Creator (3.2 reuses POST /api/files from 1.4)
require __DIR__.(config('surveyjs.lint_definitions')
    ? '/examples/lint-definition.php'           // 4.2: PUT /api/forms/{id}, linted by the SurveyJS service
    : '/examples/creator-load-save.php');       // 3.1: PUT /api/forms/{id}
require __DIR__.'/examples/ai-translation.php';                   // 3.3: POST /api/translate
require __DIR__.'/examples/variable-presets.php';                 // 3.4: GET|PUT /api/variable-presets/{formId}

// Section 4 — Run SurveyJS on your server (4.1 and 4.2 are switched in above)
require __DIR__.'/examples/server-pdf.php';                       // 4.3: GET /api/claims/{id}/pdf
require __DIR__.'/examples/extract-from-paper.php';               // 4.4: POST /api/work-orders/extract
