<?php

declare(strict_types=1);

// One file per step of the Server Integration page, in page order. Each file holds only that
// step's routes; copy the one you need. They run in the stateless "api" group (bootstrap/app.php).

// Section I — Run forms
require __DIR__.(config('surveyjs.validate_responses')
    ? '/examples/validate-response.php'     // IV.1: POST /api/responses validated by the SurveyJS service
    : '/examples/save-response.php');       // I.1: POST /api/responses
