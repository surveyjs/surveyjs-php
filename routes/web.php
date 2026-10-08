<?php

declare(strict_types=1);

use App\Http\Middleware\DemoUser;
use App\Support\Demo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Demo only: the catalog and the step pages around the examples. The integration code is in
// routes/examples/<slug>.php; these routes only show it running.

Route::get('/', fn () => view('catalog', ['manifest' => Demo::manifest()]))->name('catalog');

Route::get('/examples/{slug}', function (Request $request, string $slug) {
    [$id, $step] = Demo::step($slug) ?? abort(404);
    DemoUser::signInForPage($slug, $request);

    return view('examples.'.$slug, ['stepId' => $id, 'step' => $step]);
})->where('slug', '[a-z0-9-]+')->name('example');

// Shared client modules, definitions and samples live outside public/: serve only those three folders
Route::get('/shared/{path}', function (string $path) {
    $file = realpath(base_path('shared/'.$path));
    $allowed = array_map(fn ($dir) => realpath(base_path('shared/'.$dir)).DIRECTORY_SEPARATOR, ['client', 'definitions', 'samples']);
    abort_unless($file && is_file($file) && collect($allowed)->contains(fn ($dir) => str_starts_with($file, $dir)), 404);

    $type = match (pathinfo($file, PATHINFO_EXTENSION)) {
        'js', 'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'pdf' => 'application/pdf',
        default => 'application/octet-stream',
    };

    return response()->file($file, ['Content-Type' => $type]);
})->where('path', '.+');

// "What the server stored": a fixed read per step, never arbitrary SQL
Route::get('/demo/stored/{slug}', function (Request $request, string $slug) {
    $stored = Demo::stored($slug, $request) ?? abort(404);

    return response()->json($stored, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
})->where('slug', '[a-z0-9-]+');
