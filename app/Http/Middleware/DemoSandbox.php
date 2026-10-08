<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Sandbox;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo only: with DEMO_MODE=true each visitor works in a private copy of the seeded database
 * and uploads folder, chosen by the demo_sid cookie. Step routes don't change. Delete this file
 * (and its line in bootstrap/app.php) when you copy a step into your app.
 */
class DemoSandbox
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('surveyjs.demo_mode')) {
            return $next($request);
        }

        $id = $request->cookies->get('demo_sid');
        $issueCookie = false;

        // An invite link (?join=<id>) adopts the inviter's sandbox, after the same format check
        $join = $request->query('join');
        if ($join !== null && ! $request->is('api/*')) {
            if (Sandbox::exists($join)) {
                $issueCookie = $id !== $join;
                $id = $join;
            } else {
                $request->attributes->set('demo_join_failed', true);
                $id = null;
            }
        }

        if (! Sandbox::isValidId($id)) {
            $id = Sandbox::newId();     // never a file path built from client input
            $issueCookie = true;
        }

        Sandbox::activate($id);
        $request->attributes->set('demo_sid', $id);

        if ($limited = $this->limit($request, $id)) {
            return $limited;
        }

        $response = $next($request);

        if ($issueCookie) {
            $response->headers->setCookie(Cookie::create('demo_sid', $id, now()->addDays(30), '/', null, $request->isSecure(), false, false, Cookie::SAMESITE_LAX));
        }

        return $response;
    }

    /** Uploads are capped at 5 MB per file, and the AI endpoints at 20 calls per visitor per day. */
    private function limit(Request $request, string $id): ?Response
    {
        $maxBytes = config('surveyjs.demo.max_upload_kb') * 1024;
        foreach ($request->allFiles() as $files) {
            foreach (is_array($files) ? $files : [$files] as $file) {
                if ($file->getSize() > $maxBytes) {
                    return response()->json(['error' => 'Files over 5 MB are not accepted on the demo'], 413);
                }
            }
        }

        if ($request->is('api/translate', 'api/work-orders/extract')) {
            $key = 'demo-ai:'.$id;
            if (RateLimiter::tooManyAttempts($key, config('surveyjs.demo.ai_calls_per_day'))) {
                return response()->json(['error' => 'The demo allows 20 AI calls per visitor per day'], 429);
            }
            RateLimiter::hit($key, 86400);
        }

        return null;
    }
}
