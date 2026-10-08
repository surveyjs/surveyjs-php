<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:II.server
// GET /api/responses?formId=&from=&limit=&offset= — the stored answers as an array
Route::get('/api/responses', function (Request $request) {
    $documents = DB::table('responses')
        ->where('form_id', $request->query('formId'))
        ->where('created_at', '>=', $request->query('from', '1970-01-01'))   // ISO-8601 UTC strings compare as dates
        ->orderBy('created_at')
        ->limit(min(max((int) $request->query('limit', 500), 1), 5000))       // filter or page on the server for large volumes
        ->offset(max((int) $request->query('offset', 0), 0))
        ->pluck('data');

    return response('['.$documents->implode(',').']')                        // documents as stored, not re-encoded
        ->header('Content-Type', 'application/json');
});
// #endregion
