<?php

use App\Http\Middleware\DemoCacheHeader;
use App\Http\Middleware\DemoSandbox;
use App\Http\Middleware\DemoUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // The step routes: stateless (no CSRF token for fetch), paths written in full as on the page
        api: __DIR__.'/../routes/examples.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Store JSON exactly as it arrives: don't trim strings or turn "" into null under /api
        $middleware->trimStrings(except: [fn (Request $request) => $request->is('api/*')]);
        $middleware->convertEmptyStringsToNull(except: [fn (Request $request) => $request->is('api/*')]);

        // Demo only: the page sets these cookies directly in the browser
        $middleware->encryptCookies(except: ['demo_user', 'demo_sid']);
        $middleware->web(append: [DemoSandbox::class, DemoUser::class]);
        $middleware->api(append: [DemoSandbox::class, DemoUser::class, DemoCacheHeader::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Bad JSON in a request body is the client's fault
        $exceptions->render(function (JsonException $e, Request $request) {
            return $request->is('api/*') ? response()->json(['error' => 'The request body is not valid JSON'], 400) : null;
        });

        // The same error shape on every platform: { error } with the HTTP status (403, 404, 429, 5xx…)
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $e instanceof HttpExceptionInterface && $e->getMessage() !== ''
                ? $e->getMessage()
                : (Response::$statusTexts[$status] ?? 'Server Error');

            return response()->json(['error' => $message], $status, $e instanceof HttpExceptionInterface ? $e->getHeaders() : []);
        });
    })->create();
