<?php

declare(strict_types=1);

use App\Integration\StoredJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// #region sjs:1.7.server
// POST /api/claims — original JSON first, then the columns you query
Route::post('/api/claims', function (Request $request) {
    // The raw body, not $request->input(): PHP arrays would turn {} into [] in the stored JSON
    $body = json_decode($request->getContent(), flags: JSON_THROW_ON_ERROR);
    abort_unless(isset($body->data, $body->definitionVersion), 400, 'Expected { data, definitionVersion }');

    $id = DB::transaction(function () use ($body) {
        $responseId = StoredJson::insertResponse('claim', $body->data, [          // the original, stored as it arrived: 1.0 stays 1.0
            'definition_version' => $body->definitionVersion,                    // keep the original and its version
        ]);
        DB::table('claims')->insert([                                            // keys come from valueName
            'response_id' => $responseId,
            'customer_email' => $body->data->customer_email ?? null,
            'amount' => $body->data->amount ?? null,
        ]);

        return $responseId;
    });

    return response()->json(['id' => $id], 201);
});
// #endregion
