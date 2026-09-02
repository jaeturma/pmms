<?php

use App\Enums\UserRole;
use App\Models\CoachOnboardingRequest;
use App\Models\Personnel;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('users cannot delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/settings/profile', [
            'password' => 'password',
        ]);

    $response->assertMethodNotAllowed();

    expect($user->fresh())->not->toBeNull();
});

test('coaches can update their profile photo and linked accreditation records', function () {
    Storage::fake(config('uploads.disk'));
    $coach = User::factory()->create(['role' => UserRole::Coach]);
    $onboarding = CoachOnboardingRequest::query()->create(['user_id' => $coach->id]);
    $personnel = Personnel::factory()->create(['user_id' => $coach->id]);

    $this->actingAs($coach)
        ->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('coach.jpg', 1200, 1500)])
        ->assertSessionHasNoErrors();

    $uploadId = $coach->refresh()->profile_photo_upload_id;
    expect($uploadId)->not->toBeNull()
        ->and($onboarding->refresh()->profile_upload_id)->toBe($uploadId)
        ->and($personnel->refresh()->photo_upload_id)->toBe($uploadId);

    $this->get(route('profile.photo'))->assertOk();
});

test('tournament ICT can update their own profile photo', function () {
    Storage::fake(config('uploads.disk'));
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);

    $this->actingAs($ict)
        ->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('ict.png', 800, 1000)])
        ->assertSessionHasNoErrors();

    expect($ict->refresh()->profile_photo_upload_id)->not->toBeNull();
});

test('ordinary viewers cannot upload account profile photos', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)
        ->post(route('profile.photo.update'), ['photo' => UploadedFile::fake()->image('viewer.jpg')])
        ->assertForbidden();

    expect($viewer->refresh()->profile_photo_upload_id)->toBeNull();
});
