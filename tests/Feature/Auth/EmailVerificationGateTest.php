<?php

use App\Models\Setting;
use App\Models\User;

test('unverified users can reach verified-only routes while enforcement is inactive', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});

test('unverified users are redirected to the verification prompt once enforcement is active', function () {
    Setting::current()->forceFill([
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_username' => 'mailer',
        'smtp_password' => 'mailer-secret',
        'smtp_from_address' => 'no-reply@example.test',
        'email_verification_enabled' => true,
    ])->save();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('already-verified users are unaffected by enforcement being active', function () {
    Setting::current()->forceFill([
        'smtp_host' => 'smtp.example.test',
        'smtp_port' => 587,
        'smtp_username' => 'mailer',
        'smtp_password' => 'mailer-secret',
        'smtp_from_address' => 'no-reply@example.test',
        'email_verification_enabled' => true,
    ])->save();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
