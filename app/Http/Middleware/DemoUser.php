<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo only: the page header switches between Alice (premium, editor), Bob (basic, viewer)
 * and Signed out with a demo_user cookie. Real apps authenticate here with the session guard
 * or Sanctum; the steps only call Auth::user() and the gates in AppServiceProvider.
 */
class DemoUser
{
    public const USERS = ['alice' => 'alice@example.com', 'bob' => 'bob@example.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $email = self::USERS[$request->cookies->get('demo_user')] ?? null;
        $user = $email ? User::where('email', $email)->first() : null;

        if ($user) {
            Auth::setUser($user);
        }

        return $next($request);
    }
}
