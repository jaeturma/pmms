<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Setting::current()->forceFill([
        'recaptcha_enabled' => true,
        'recaptcha_site_key' => 'site-key',
        'recaptcha_secret_key' => 'secret-key',
    ])->save();
});

test('login is blocked without a reCAPTCHA response once enabled and configured', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('recaptcha');

    $this->assertGuest();
});

test('login succeeds once the reCAPTCHA token verifies', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
    ]);

    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'g-recaptcha-response' => 'a-token',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('registration is blocked without a reCAPTCHA response once enabled and configured', function () {
    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'code_challenge' => 'ABC12',
    ])->assertSessionHasErrors('recaptcha');

    $this->assertGuest();
});

test('registration succeeds once the reCAPTCHA token verifies', function () {
    Http::fake([
        'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
    ]);

    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'g-recaptcha-response' => 'a-token',
        'code_challenge' => 'ABC12',
    ])->assertRedirect(route('login', absolute: false));

    $this->assertGuest();
});

test('the login page shares the reCAPTCHA site key with guests only when it is ready', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page
            ->where('recaptcha.enabled', true)
            ->where('recaptcha.siteKey', 'site-key'));

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('recaptcha', null));
});
