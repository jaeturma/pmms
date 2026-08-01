<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;

/**
 * Replaces the framework's default `verified` middleware alias (see
 * `bootstrap/app.php`) so enforcement is toggleable: `User::hasVerifiedEmail()`
 * stays the real, unmodified `email_verified_at` check (existing verification
 * tests depend on that), and this wraps it with a system-settings gate
 * instead — the check only runs at all once the admin has both enabled email
 * verification and SMTP is actually working
 * (`Setting::emailVerificationActive()`). Newly registered accounts start
 * unverified either way; this is what stops that from mattering while the
 * feature is off.
 */
class EnsureEmailIsVerifiedIfRequired extends EnsureEmailIsVerified
{
    /**
     * @param  mixed  $request
     * @param  string|null  $redirectToRoute
     */
    public function handle($request, Closure $next, $redirectToRoute = null): mixed
    {
        if (! Setting::current()->emailVerificationActive()) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute);
    }
}
