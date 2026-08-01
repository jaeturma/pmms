<?php

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected from system settings', function () {
    $this->get('/system-settings')->assertRedirect('/login');
});

test('only admins can view or update system settings', function (User $user) {
    $this->actingAs($user)
        ->get('/system-settings')
        ->assertForbidden();

    $this->actingAs($user)
        ->put('/system-settings', ['recaptcha_enabled' => false, 'email_verification_enabled' => false])
        ->assertForbidden();
})->with([
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('admins see the settings page with secrets never round-tripped', function () {
    Setting::current()->forceFill([
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => 'site-key',
        'recaptcha_secret_key' => 'secret-key',
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_username' => 'mailer',
        'smtp_password' => 'mailer-secret',
        'smtp_from_address' => 'no-reply@example.test',
    ])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/system-settings')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('system-settings/edit')
            ->where('settings.recaptcha_enabled', true)
            ->where('settings.recaptcha_site_key', 'site-key')
            ->where('settings.has_recaptcha_secret_key', true)
            ->where('settings.recaptcha_ready', true)
            ->where('settings.has_smtp_password', true)
            ->where('settings.smtp_ready', true)
            ->missing('settings.recaptcha_secret_key')
            ->missing('settings.smtp_password'));
});

test('admins can save settings, and audit trail records it', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put('/system-settings', [
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'site-key',
            'recaptcha_secret_key' => 'secret-key',
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_username' => 'mailer',
            'smtp_password' => 'mailer-secret',
            'smtp_encryption' => 'tls',
            'smtp_from_address' => 'no-reply@example.test',
            'smtp_from_name' => 'PMMS',
            'email_verification_enabled' => false,
        ])
        ->assertRedirect();

    $settings = Setting::current();

    expect($settings->recaptcha_enabled)->toBeTrue()
        ->and($settings->recaptcha_site_key)->toBe('site-key')
        ->and($settings->recaptcha_secret_key)->toBe('secret-key')
        ->and($settings->smtp_password)->toBe('mailer-secret')
        ->and(AuditLog::query()->where('action', 'system_settings.updated')->exists())->toBeTrue();
});

test('a blank secret on save leaves the previously stored secret unchanged', function () {
    Setting::current()->forceFill([
        'recaptcha_secret_key' => 'original-secret',
        'smtp_password' => 'original-password',
    ])->save();

    $this->actingAs(User::factory()->admin()->create())
        ->put('/system-settings', [
            'recaptcha_enabled' => false,
            'recaptcha_secret_key' => '',
            'smtp_password' => '',
            'email_verification_enabled' => false,
        ])
        ->assertRedirect();

    $settings = Setting::current();

    expect($settings->recaptcha_secret_key)->toBe('original-secret')
        ->and($settings->smtp_password)->toBe('original-password');
});

test('enabling email verification grandfathers every existing unverified account', function () {
    $preExisting = User::factory()->unverified()->create();
    $alreadyVerified = User::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put('/system-settings', [
            'recaptcha_enabled' => false,
            'smtp_host' => 'smtp.example.test',
            'smtp_port' => 587,
            'smtp_username' => 'mailer',
            'smtp_password' => 'mailer-secret',
            'smtp_from_address' => 'no-reply@example.test',
            'email_verification_enabled' => true,
        ])
        ->assertRedirect();

    expect(Setting::current()->emailVerificationActive())->toBeTrue()
        ->and($preExisting->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and($alreadyVerified->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'system_settings.email_verification_grandfathered')->exists())->toBeTrue();
});

test('enabling email verification without complete SMTP settings does not grandfather or activate', function () {
    $preExisting = User::factory()->unverified()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put('/system-settings', [
            'recaptcha_enabled' => false,
            'email_verification_enabled' => true,
        ])
        ->assertRedirect();

    expect(Setting::current()->emailVerificationActive())->toBeFalse()
        ->and($preExisting->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'system_settings.email_verification_grandfathered')->exists())->toBeFalse();
});
