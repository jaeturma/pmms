<?php

use App\Models\CoachOnboardingRequest;
use App\Models\District;
use App\Models\Event;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
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
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'code_challenge' => 'ABC12',
    ]);

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $this->assertDatabaseHas('users', ['email' => 'test@example.com', 'approval_status' => 'pending']);
});

test('administrators can suspend regular and coach account registrations independently', function () {
    Setting::current()->forceFill(['user_registration_enabled' => false])->save();

    $this->post(route('register.store'), [
        'name' => 'Suspended User',
        'email' => 'suspended@example.com',
    ])->assertSessionHasErrors('registration');

    Setting::current()->forceFill([
        'user_registration_enabled' => true,
        'coach_registration_enabled' => false,
    ])->save();

    $this->post(route('register.store'), [
        'name' => 'Suspended Coach',
        'email' => 'suspended-coach@example.com',
        'account_type' => 'coach',
    ])->assertSessionHasErrors('registration');

    $this->assertDatabaseMissing('users', ['email' => 'suspended@example.com']);
    $this->assertDatabaseMissing('users', ['email' => 'suspended-coach@example.com']);
});

test('registration enforces account field character limits', function () {
    $this->get(route('register'));

    $this->post(route('register.store'), [
        'name' => str_repeat('N', 51),
        'email' => str_repeat('e', 40).'@example.com',
        'password' => str_repeat('p', 21),
        'password_confirmation' => str_repeat('p', 21),
        'code_challenge' => 'ABC12',
    ])->assertSessionHasErrors(['name', 'email', 'password']);

    $this->assertGuest();
});

test('registration requires a strong password', function (string $password) {
    $this->get(route('register'));

    $this->post(route('register.store'), [
        'name' => 'Password Test',
        'email' => 'password-test@example.com',
        'password' => $password,
        'password_confirmation' => $password,
        'code_challenge' => 'ABC12',
    ])->assertSessionHasErrors('password');

    $this->assertGuest();
})->with([
    'missing lowercase letter' => 'PASSWORD1!',
    'missing capital letter' => 'password1!',
    'missing number' => 'Password!',
    'missing special character' => 'Password1',
]);

test('coach registration requires and stores a municipality team', function () {
    $municipality = District::factory()->create();
    $events = Event::factory()->count(2)->create();

    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Coach User',
        'email' => 'coach@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'account_type' => 'coach',
        'code_challenge' => 'ABC12',
    ])->assertSessionHasErrors('district_id');

    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Coach User',
        'email' => 'coach@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'account_type' => 'coach',
        'district_id' => $municipality->id,
        'event_ids' => $events->modelKeys(),
        'coach_profile' => UploadedFile::fake()->image('coach.jpg'),
        'coach_certification' => UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf'),
        'code_challenge' => 'ABC12',
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('coach_onboarding_requests', [
        'district_id' => $municipality->id,
        'event_id' => $events->first()->id,
    ]);
    expect(CoachOnboardingRequest::query()->sole()->profile_upload_id)->not->toBeNull()
        ->and(CoachOnboardingRequest::query()->sole()->certification_upload_id)->not->toBeNull();
    foreach ($events as $event) {
        $this->assertDatabaseHas('coach_onboarding_request_event', ['event_id' => $event->id]);
    }
    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'coach@example.com',
        'approval_status' => 'pending',
    ]);

    $this->get(route('register'));
    $this->post(route('register.store'), [
        'name' => 'Coach Without Documents',
        'email' => 'coach-no-documents@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'account_type' => 'coach',
        'district_id' => $municipality->id,
        'event_ids' => [$events->first()->id],
        'code_challenge' => 'ABC12',
    ])->assertSessionHasNoErrors();

    $withoutDocuments = CoachOnboardingRequest::query()->whereHas('user', fn ($query) => $query
        ->where('email', 'coach-no-documents@example.com'))->sole();
    expect($withoutDocuments->profile_upload_id)->toBeNull()
        ->and($withoutDocuments->certification_upload_id)->toBeNull();
});

test('registration rejects an incorrect image verification code', function () {
    $this->get(route('register'));

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'wrong-code@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
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
