<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Demo only: a demo_user cookie picks Alice (premium, editor), Bob (basic, viewer) or Signed out
 * ("none"). Real apps authenticate here with the session guard or Sanctum; the steps only call
 * Auth::user() and the gates in AppServiceProvider.
 *
 * Only the pages where the user changes the result offer a switch (PAGES). Every other page
 * signs in as Alice, so a choice made on one page never breaks another.
 */
class DemoUser
{
    public const USERS = ['alice' => 'alice@example.com', 'bob' => 'bob@example.com'];

    public const LABELS = ['alice' => 'Alice (premium, editor)', 'bob' => 'Bob (basic, viewer)', 'none' => 'Signed out'];

    /** Step slug => the users its switch offers. The first is the default. */
    public const PAGES = [
        'load-definition-variables-data' => ['alice', 'bob', 'none'],   // {user.name} and {plan} change
        'fill-together' => ['alice', 'bob'],                            // two people in one room
        'creator-load-save' => ['alice', 'bob'],                        // Bob's saves answer 403
    ];

    public function handle(Request $request, Closure $next): Response
    {
        self::signIn($request->cookies->get('demo_user'));

        return $next($request);
    }

    /** The users a step page offers; one means no switch. */
    public static function forPage(string $slug): array
    {
        return self::PAGES[$slug] ?? ['alice'];
    }

    /** On a step page: keep the cookie's user if the page offers it, otherwise sign in as the page's default. */
    public static function signInForPage(string $slug, Request $request): void
    {
        $users = self::forPage($slug);
        if (! in_array($request->cookies->get('demo_user'), $users, true)) {
            Cookie::queue(Cookie::make('demo_user', $users[0], 525600, httpOnly: false, sameSite: 'lax'));   // host.js reads and sets it
            self::signIn($users[0]);
        }
    }

    /** The current demo user's key: alice, bob or none. */
    public static function key(): string
    {
        return array_search(Auth::user()?->email, self::USERS, true) ?: 'none';
    }

    private static function signIn(?string $key): void
    {
        $email = self::USERS[$key] ?? null;
        $user = $email ? User::where('email', $email)->first() : null;

        if ($user) {
            Auth::setUser($user);
        }
    }
}
