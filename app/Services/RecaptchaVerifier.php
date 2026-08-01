<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a Google reCAPTCHA v2 ("I'm not a robot") response token
 * against Google's siteverify endpoint. Only actually checks anything
 * when `Setting::current()->recaptchaReady()` — enabled *and* both keys
 * present — so a half-configured or disabled toggle never blocks a real
 * login/registration, and so this class has exactly one thing to get
 * right (calling Google), not also the "should we even bother" decision
 * duplicated at every call site.
 */
class RecaptchaVerifier
{
    private const string VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * @param  string|null  $token  The `g-recaptcha-response` field submitted by the widget.
     */
    public function passes(?string $token): bool
    {
        $settings = Setting::current();

        if (! $settings->recaptchaReady()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        $response = Http::asForm()->post(self::VERIFY_URL, [
            'secret' => $settings->recaptcha_secret_key,
            'response' => $token,
        ]);

        return $response->successful() && $response->json('success') === true;
    }
}
