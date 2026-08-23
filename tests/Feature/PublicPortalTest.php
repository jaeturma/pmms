<?php

use App\Enums\MatchStatus;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\District;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\Setting;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests can view the portal home without authentication', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/home')
            ->where('meet', null)
            ->has('municipalities', 0));
});

test('only the one published and active meet appears on the portal home', function () {
    $featured = Meet::factory()->active()->published()->featured()->create();
    Meet::factory()->active()->published()->create();
    Meet::factory()->active()->featured()->create();
    Meet::factory()->create();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('meet.name', $featured->name)
            ->where('meet.status_label', 'Active'));
});

test('the landing page only exposes Facebook Live when an admin enables it', function () {
    Meet::factory()->active()->published()->featured()->create();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('facebookLive', null));

    Setting::current()->forceFill([
        'facebook_live_enabled' => true,
        'facebook_live_url' => 'https://www.facebook.com/example/videos/123456789',
    ])->save();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('facebookLive.url', 'https://www.facebook.com/example/videos/123456789')
            ->where('facebookLive.embed_url', fn (string $url): bool => str_starts_with($url, 'https://www.facebook.com/plugins/video.php?')));
});

test('portal meet props carry public-safe fields only', function () {
    Meet::factory()->active()->published()->featured()->create();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('meet', fn (AssertableInertia $meet) => $meet
                ->hasAll(['id', 'name', 'school_year', 'starts_at', 'starts_at_iso', 'ends_at', 'venue', 'status_label'])
                ->missing('is_published')
                ->missing('is_active')
                ->missing('status')));
});

test('the portal home lists the active meet\'s competing municipalities, deduplicated', function () {
    $meet = Meet::factory()->active()->published()->featured()->create();

    $nabunturan = District::factory()->create(['name' => 'Nabunturan']);
    Delegation::factory()->approved()->create([
        'meet_id' => $meet->id,
        'school_id' => null,
        'district_id' => $nabunturan->id,
    ]);

    $otherMeet = Meet::factory()->active()->published()->create();
    Delegation::factory()->approved()->create([
        'meet_id' => $otherMeet->id,
        'school_id' => null,
        'district_id' => District::factory()->create(['name' => 'Compostela'])->id,
    ]);

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('municipalities', 1)
            ->where('municipalities.0.name', 'Nabunturan'));
});

test('managers can publish a non-draft meet, audited', function () {
    $meet = Meet::factory()->registrationClosed()->create();

    $this->actingAs(User::factory()->admin()->create())
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
    $meet = Meet::factory()->active()->published()->featured()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/meets/{$meet->id}/unpublish")
        ->assertRedirect();

    expect($meet->refresh()->is_published)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'meet.unpublished')->exists())->toBeTrue();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('meet', null));
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

test('the public nav points at the most recently started published meet, with a live-match count', function () {
    Meet::factory()->active()->published()->create(['starts_at' => now()->subDays(30)]);
    $latest = Meet::factory()->active()->published()->create(['starts_at' => now()]);

    $match = EventMatch::factory()->create(['meet_id' => $latest->id, 'status' => MatchStatus::Scheduled]);
    ScoringSession::factory()->create(['match_id' => $match->id]);
    ScoringSession::factory()->ended()->create([
        'match_id' => EventMatch::factory()->create(['meet_id' => $latest->id, 'status' => MatchStatus::Scheduled])->id,
    ]);

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('publicNav.meetId', $latest->id)
            ->where('publicNav.meetName', $latest->name)
            ->where('publicNav.venue', $latest->venue)
            ->where('publicNav.schoolYear', $latest->school_year)
            ->where('publicNav.liveCount', 1));
});

test('the public nav is absent when there are no published meets', function () {
    Meet::factory()->active()->create();

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('publicNav', null));
});

test('authenticated users never receive the public nav', function () {
    Meet::factory()->active()->published()->create();

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('publicNav', null));
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
