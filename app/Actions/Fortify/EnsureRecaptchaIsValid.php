<?php

namespace App\Actions\Fortify;

use App\Services\RecaptchaVerifier;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Http\Requests\LoginRequest;

/**
 * A custom step in Fortify's login pipeline (`Fortify::authenticateThrough()`
 * in `FortifyServiceProvider`) — placed first, before the throttle/2FA/
 * credential checks, so a failed reCAPTCHA never even attempts an
 * authentication attempt. `RecaptchaVerifier::passes()` is itself a no-op
 * (always true) unless the system admin has both enabled reCAPTCHA and
 * saved both keys, so this step is inert everywhere until that's true.
 */
class EnsureRecaptchaIsValid
{
    public function __construct(private readonly RecaptchaVerifier $recaptcha) {}

    /**
     * @param  LoginRequest  $request
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if (! $this->recaptcha->passes($request->input('g-recaptcha-response'))) {
            throw ValidationException::withMessages([
                'recaptcha' => [__('Please complete the reCAPTCHA challenge.')],
            ]);
        }

        return $next($request);
    }
}
