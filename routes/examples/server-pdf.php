<?php

declare(strict_types=1);

use App\Integration\SurveyJsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:4.3.server
// GET /api/claims/{id}/pdf — ask the SurveyJS service for the filled form and pass the PDF through
Route::get('/api/claims/{id}/pdf', function (string $id) {
    $data = DB::table('responses')->where('form_id', 'claim')->where('id', $id)->value('data') ?? abort(404, 'Unknown claim');
    $definition = DB::table('forms')->where('key', 'claim')->value('json');
    $pdf = SurveyJsService::post('/pdf', ['schema' => json_decode($definition), 'response' => json_decode($data)], timeout: 60);
    if ($pdf->status() !== 200 || ! str_starts_with($pdf->header('Content-Type'), 'application/pdf')) {   // fail closed
        SurveyJsService::unexpected('/pdf', $pdf);
    }

    return response($pdf->body(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"claim-{$id}.pdf\"",
    ]);
});
// #endregion
