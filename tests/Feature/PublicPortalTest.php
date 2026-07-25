<?php

use App\Models\AuditLog;
use App\Models\Meet;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests can view the portal home without authentication', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/home')
            ->has('meets', 0));
});

test('only published meets appear on the portal home', function () {
    $published = Meet::factory()->active()->published()->create();
    Meet::factory()->active()->create();
    Meet::factory()->create();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meets', 1)
            ->where('meets.0.name', $published->name)
            ->where('meets.0.status_label', 'Active'));
});

test('portal meet props carry public-safe fields only', function () {
    Meet::factory()->active()->published()->create();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meets.0', fn (AssertableInertia $meet) => $meet
                ->hasAll(['id', 'name', 'school_year', 'starts_at', 'ends_at', 'venue', 'status_label'])
                ->missing('is_published')
                ->missing('status')));
});

test('managers can publish a non-draft meet, audited', function () {
    $meet = Meet::factory()->registrationClosed()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->patch("/meets/{$meet->id}/publish")
        ->assertRedirect();

    expect($meet->refresh()->is_published)->toBeTrue()
        ->and(AuditLog::query()->where('action', 'meet.published')->exists())->toBeTrue();
});

test('draft meets cannot be published', function () {
    $meet = Meet::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meets/{$meet->id}/publish")
        ->assertRedirect();

    expect($meet->refresh()->is_published)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'meet.published')->exists())->toBeFalse();
});

test('unpublishing removes the meet from the portal immediately, audited', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meets/{$meet->id}/unpublish")
        ->assertRedirect();

    expect($meet->refresh()->is_published)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'meet.unpublished')->exists())->toBeTrue();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page->has('meets', 0));
});

test('an unpublished or nonexistent meet renders the branded not-found page for guests', function () {
    $unpublished = Meet::factory()->active()->create();

    foreach (["/meets/{$unpublished->id}", '/meets/999999'] as $url) {
        $this->get($url)
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('error')
                ->where('status', 404));
    }
});

test('the not-found page also renders for authenticated users', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/meets/999999')
        ->assertNotFound()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('error')
            ->where('status', 404));
});

test('viewers and delegation officers cannot publish or unpublish', function (User $user) {
    $meet = Meet::factory()->active()->create();

    $this->actingAs($user)
        ->patch("/meets/{$meet->id}/publish")
        ->assertForbidden();

    $published = Meet::factory()->active()->published()->create();

    $this->actingAs($user)
        ->patch("/meets/{$published->id}/unpublish")
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);
