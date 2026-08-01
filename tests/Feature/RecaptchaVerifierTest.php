<?php

use App\Models\Setting;
use App\Services\RecaptchaVerifier;
use Illuminate\Support\Facades\Http;

test('passes is a no-op when reCAPTCHA is not enabled or not fully configured', function () {
    expect(app(RecaptchaVerifier::class)->passes(null))->toBeTrue();

    Setting::current()->forceFill(['recaptcha_enabled' => true, 'recaptcha_site_key' => 'site-key'])->save();

    expect(app(RecaptchaVerifier::class)->passes(null))->toBeTrue();
});

test('passes fails a blank token once reCAPTCHA is fully configured', function () {
    Setting::current()->forceFill([
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => 'site-key',
        'recaptcha_secret_key' => 'secret-key',
    ])->save();

    expect(app(RecaptchaVerifier::class)->passes(null))->toBeFalse();
});

test('passes accepts a token Google verifies as successful', function () {
    Setting::current()->forceFill([
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => 'site-key',
        'recaptcha_secret_key' => 'secret-key',
    ])->save();

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
    ]);

    expect(app(RecaptchaVerifier::class)->passes('a-token'))->toBeTrue();
});

test('passes rejects a token Google verifies as unsuccessful', function () {
    Setting::current()->forceFill([
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => 'site-key',
        'recaptcha_secret_key' => 'secret-key',
    ])->save();

    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
    ]);

    expect(app(RecaptchaVerifier::class)->passes('a-token'))->toBeFalse();
});
