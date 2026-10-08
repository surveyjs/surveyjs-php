<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// #region sjs:I.2.server
// Option A — render the definition into the page: no database, no extra request
Route::get('/claim', function () {
    $definition = json_decode(file_get_contents(base_path('shared/definitions/load-definition-variables-data.json')));
    $definition->title = 'Hi '.Str::before(Auth::user()->name ?? 'there', ' ');   // change it per request if you like

    return view('examples.load-definition-variables-data', ['definition' => $definition]);   // Js::from() escapes it for an inline script
});

// Option B — definition, variables and previous answers in one response
Route::get('/api/forms/{id}', function (Request $request, string $id) {
    $user = Auth::user();
    $definition = DB::table('forms')->where('key', $id)->value('json') ?? abort(404, 'Unknown form');
    $data = $request->filled('record') ? DB::table('responses')->where('id', $request->query('record'))->value('data') : null;

    return response()->json([
        'definition' => json_decode($definition),                                // a stored document, as stored
        'variables' => ['user' => ['name' => $user?->name, 'plan' => $user?->plan]],
        'data' => $data === null ? new stdClass : json_decode($data),           // previous answers, or {}
    ], options: JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
});
// #endregion
