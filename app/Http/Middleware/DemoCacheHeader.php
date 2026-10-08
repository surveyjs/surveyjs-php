<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo only: adds X-Demo-Cache: hit / miss / fallback to GET /api/countries so the requests log
 * shows where the list came from. It watches cache events, so the I.5 route stays as on the page.
 */
class DemoCacheHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/countries')) {
            return $next($request);
        }

        $seen = [];
        $record = function (object $event) use (&$seen) {
            if ($event->key === 'countries') {
                $seen[] = $event::class;
            }
        };
        Event::listen([CacheHit::class, CacheMissed::class, KeyWritten::class], $record);

        $response = $next($request);

        $state = match (true) {
            in_array(CacheHit::class, $seen, true) => 'hit',
            in_array(KeyWritten::class, $seen, true) => 'miss',
            default => 'fallback',
        };
        $response->headers->set('X-Demo-Cache', $state);

        return $response;
    }
}
