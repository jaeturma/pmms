<?php

use App\Models\District;
use App\Models\Event;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users register pending approval and are not logged in', function () {
    $this->get(route('register'));
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'code_challenge' => 'ABC12',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'approval_status' => 'pending']);
});

test('coach registration requires and stores a municipality team', function () {
    $municipality = District::factory()->create();
    $events = Event::factory()->count(2)->create();

    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Coach User',
        'email' => 'coach@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'coach',
        'code_challenge' => 'ABC12',
    ])->assertSessionHasErrors('district_id');

    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Coach User',
        'email' => 'coach@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'coach',
        'district_id' => $municipality->id,
        'event_ids' => $events->modelKeys(),
        'code_challenge' => 'ABC12',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('coach_onboarding_requests', [
        'district_id' => $municipality->id,
        'event_id' => $events->first()->id,
    ]);
    foreach ($events as $event) {
        $this->assertDatabaseHas('coach_onboarding_request_event', ['event_id' => $event->id]);
    }
    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'coach@example.com',
        'approval_status' => 'pending',
    ]);
});

test('registration rejects an incorrect image verification code', function () {
    $this->get(route('register'));

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'wrong-code@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'code_challenge' => 'WRONG',
    ])->assertSessionHasErrors('code_challenge');

    $this->assertGuest();
});

test('registration is rate limited (WP-06-03)', function () {
    RateLimiter::increment('register:127.0.0.1', amount: 5);

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertTooManyRequests();
    $this->assertGuest();
});
