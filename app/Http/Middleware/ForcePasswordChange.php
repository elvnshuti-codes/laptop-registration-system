<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    private const MAX_PASSWORD_AGE_DAYS = 30;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && $this->passwordChangeRequired(auth()->user())) {
            if (!$request->routeIs('password.change', 'password.change.submit', 'logout')) {
                return redirect()->route('password.change');
            }
        }

        return $next($request);
    }

    private function passwordChangeRequired($user): bool
    {
        if ($user->must_change_password) {
            return true;
        }

        if ($user->password_changed_at === null) {
            return false;
        }

        return $user->password_changed_at->lt(now()->subDays(self::MAX_PASSWORD_AGE_DAYS));
    }
}