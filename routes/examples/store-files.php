<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// #region sjs:I.4.server
// POST /api/files — store uploads, return their ids in the same order
Route::post('/api/files', function (Request $request) {
    $ids = [];
    foreach (Arr::wrap($request->file('files')) as $file) {   // field "files[]": PHP keeps only the last of several "files"
        abort_unless($file->isValid(), 400, $file->getErrorMessage());
        $id = (string) Str::uuid();
        $file->storeAs('', $id, 'uploads');                    // the "uploads" disk: local, S3…; named by id, never by the client
        DB::table('files')->insert(['id' => $id, 'name' => $file->getClientOriginalName(), 'type' => $file->getMimeType()]);
        $ids[] = $id;
    }

    return response()->json($ids);
});

// GET /api/files/{id} — the content, for private files only; check access first
Route::get('/api/files/{id}', function (string $id) {
    $file = DB::table('files')->find($id);
    abort_if(! $file || Gate::denies('read-file', $file), 404);   // 404, not 403: don't confirm the file exists

    return Storage::disk('uploads')->response($id, $file->name, ['Content-Type' => $file->type]);
});
// #endregion
