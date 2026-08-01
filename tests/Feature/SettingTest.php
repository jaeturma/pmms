<?php

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

test('Setting::current creates the single settings row on first access', function () {
    expect(Setting::query()->count())->toBe(0);

    $settings = Setting::current();

    expect(Setting::query()->count())->toBe(1)
        ->and(Setting::current()->id)->toBe($settings->id);
});

test('recaptchaReady requires enabled plus both keys', function () {
    $settings = Setting::current();

    expect($settings->recaptchaReady())->toBeFalse();

    $settings->forceFill(['recaptcha_enabled' => true])->save();
    expect($settings->fresh()->recaptchaReady())->toBeFalse();

    $settings->forceFill(['recaptcha_site_key' => 'site-key'])->save();
    expect($settings->fresh()->recaptchaReady())->toBeFalse();

    $settings->forceFill(['recaptcha_secret_key' => 'secret-key'])->save();
    expect($settings->fresh()->recaptchaReady())->toBeTrue();
});

test('smtpReady requires every mail field to be filled', function () {
    $settings = Setting::current();

    expect($settings->smtpReady())->toBeFalse();

    $settings->forceFill([
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_username' => 'mailer',
        'smtp_password' => 'mailer-secret',
        'smtp_from_address' => 'no-reply@example.test',
    ])->save();

    expect($settings->fresh()->smtpReady())->toBeTrue();
});

test('emailVerificationActive requires the toggle plus a working SMTP configuration', function () {
    $settings = Setting::current();

    expect($settings->emailVerificationActive())->toBeFalse();

    $settings->forceFill(['email_verification_enabled' => true])->save();
    expect($settings->fresh()->emailVerificationActive())->toBeFalse();

    $settings->forceFill([
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_username' => 'mailer',
        'smtp_password' => 'mailer-secret',
        'smtp_from_address' => 'no-reply@example.test',
    ])->save();

    expect($settings->fresh()->emailVerificationActive())->toBeTrue();
});

test('secret fields are stored encrypted at rest', function () {
    Setting::current()->forceFill([
        'recaptcha_secret_key' => 'plain-secret',
        'smtp_password' => 'plain-password',
    ])->save();

    $raw = DB::table('system_settings')->first();

    expect($raw->recaptcha_secret_key)->not->toBe('plain-secret')
        ->and($raw->smtp_password)->not->toBe('plain-password');
});
