<?php

declare(strict_types=1);

// One file per step of the Server Integration page, in page order. Each file holds only that
// step's routes; copy the one you need. They run in the stateless "api" group (bootstrap/app.php).
// fill-together and edit-together are WebSocket relays, run as their own processes: app/Relay, `php artisan relay:fill|edit`.

// Run Forms
require __DIR__.(config('surveyjs.validate_responses')
    ? '/examples/validate-response.php'         // POST /api/responses, validated by the SurveyJS service
    : '/examples/save-response.php');           // POST /api/responses
require __DIR__.'/examples/load-definition-variables-data.php';   // GET /claim, GET /api/forms/{id}
require __DIR__.'/examples/resume-progress.php';                  // GET|PUT /api/progress/{formId}
require __DIR__.'/examples/store-files.php';                      // POST /api/files, GET /api/files/{id}
require __DIR__.'/examples/choices-from-web.php';                 // GET /api/offices, GET /api/countries
require __DIR__.'/examples/async-functions.php';                  // GET /api/customers/exists, GET /api/shipping
require __DIR__.'/examples/relational-storage.php';               // POST /api/claims

// Analyze Results with Dashboard
require __DIR__.'/examples/dashboard.php';                        // 2:  GET /api/responses

// Edit Forms in Survey Creator (creator-uploads reuses POST /api/files from store-files)
require __DIR__.(config('surveyjs.lint_definitions')
    ? '/examples/lint-definition.php'           // PUT /api/forms/{id}, linted by the SurveyJS service
    : '/examples/creator-load-save.php');       // PUT /api/forms/{id}
require __DIR__.'/examples/ai-translation.php';                   // POST /api/translate
require __DIR__.'/examples/variable-presets.php';                 // GET|PUT /api/variable-presets/{formId}

// Run Server-Side Workflows (validate-response and lint-definition are switched in above)
require __DIR__.'/examples/server-pdf.php';                       // GET /api/claims/{id}/pdf
require __DIR__.'/examples/extract-from-paper.php';               // POST /api/work-orders/extract
