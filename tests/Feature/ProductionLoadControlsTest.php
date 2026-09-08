<?php

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Models\Setting;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

/** A published, active meet with a live basketball match + session. */
function loadControlLiveMatch(): array
{
    $meet = Meet::factory()->active()->published()->featured()->create();
    $sport = Sport::factory()->create(['name' => 'Basketball']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $match = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'status' => 'scheduled',
        'live_scoring_enabled' => true,
    ]);
    $session = ScoringSession::factory()->create([
        'match_id' => $match->id,
        'score_a' => 12,
        'score_b' => 8,
    ]);

    return [$meet, $match, $session];
}

function suspendScoreboards(): void
{
    Setting::current()->forceFill(['live_scoreboards_suspended' => true])->save();
    Setting::forgetLoadControlsCache();
}

function enableInactivityExpiry(int $minutes = 5): void
{
    Setting::current()->forceFill([
        'authenticated_inactivity_expiry_enabled' => true,
        'authenticated_inactivity_timeout_minutes' => $minutes,
    ])->save();
    Setting::forgetLoadControlsCache();
}

/** Seed a row straight into the sessions table (production uses the database driver). */
function seedSession(string $id, ?int $userId, int $lastActivity): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'phpunit',
        'payload' => base64_encode('x'),
        'last_activity' => $lastActivity,
    ]);
}

// ============================================================
// A. Medal Tally — no automatic polling
// ============================================================

test('the public medal tally page contains no automatic polling', function () {
    $source = file_get_contents(resource_path('js/apps/portal/pages/portal/tally.tsx'));

    expect($source)
        ->not->toContain('setInterval')
        ->not->toContain('TALLY_POLL_INTERVAL_MS')
        ->not->toContain('usePortalPageVisible');

    // The manual control is a plain router.reload of the current URL,
    // which preserves ?sport_id and the ?category in the address bar.
    expect($source)->toContain('Refresh Tally')
        ->and($source)->toContain('router.reload');
});

test('a manual tally refresh returns the current standings and preserves the sport filter', function () {
    $meet = Meet::factory()->active()->published()->create();
    $sport = Sport::factory()->create(['name' => 'Basketball']);

    $first = $this->get("/meets/{$meet->id}/tally?sport_id={$sport->id}&category=secondary")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/tally')
            ->where('filters.sport_id', $sport->id));

    // A second request (what "Refresh Tally" issues) reflects fresh data
    // and keeps the same filter.
    $this->get("/meets/{$meet->id}/tally?sport_id={$sport->id}&category=secondary")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('filters.sport_id', $sport->id));
});

// ============================================================
// B. Suspend public live scoreboards
// ============================================================

test('a System Administrator can suspend and resume public live scoreboards', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/system/load-controls/suspend-scoreboards')->assertRedirect();
    expect(Setting::current()->fresh()->live_scoreboards_suspended)->toBeTrue();
    expect(AuditLog::where('action', 'load_control.scoreboards_suspended')->count())->toBe(1);

    $this->actingAs($admin)->post('/system/load-controls/resume-scoreboards')->assertRedirect();
    expect(Setting::current()->fresh()->live_scoreboards_suspended)->toBeFalse();
    expect(AuditLog::where('action', 'load_control.scoreboards_resumed')->count())->toBe(1);
});

test('a guest cannot reach the load-control endpoints', function () {
    $this->post('/system/load-controls/suspend-scoreboards')->assertRedirect('/login');
    $this->post('/system/load-controls/disconnect-inactive')->assertRedirect('/login');
});

test('non-administrators cannot suspend or resume public scoreboards', function (User $user) {
    $this->actingAs($user)->post('/system/load-controls/suspend-scoreboards')->assertForbidden();
    $this->actingAs($user)->post('/system/load-controls/resume-scoreboards')->assertForbidden();
})->with([
    'organizer' => fn () => User::factory()->organizer()->create(),
    'tournament ICT' => fn () => User::factory()->create(['role' => 'tournament_ict']),
    'technical official' => fn () => User::factory()->technicalOfficial()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('a suspended public scoreboard page renders the lightweight suspended screen', function () {
    [$meet, $match] = loadControlLiveMatch();
    suspendScoreboards();

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('portal/scoreboard-suspended'));

    $this->get('/live/basketball')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('portal/scoreboard-suspended'));
});

test('a suspended public scoreboard poll returns a tiny body and does no scoreboard work', function () {
    [$meet, $match] = loadControlLiveMatch();
    suspendScoreboards();

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard/poll")
        ->assertOk()
        ->assertExactJson(['suspended' => true, 'session' => null]);

    $this->get('/basketball/poll')
        ->assertOk()
        ->assertExactJson(['suspended' => true, 'liveNow' => null, 'otherLiveCount' => 0]);

    // No scoring_sessions / score_events / match hydration — the gate
    // short-circuits before the controller. (A couple of framework
    // queries — session, settings cache — are expected; the expensive
    // scoreboard queries are not.)
    $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
    expect(str_contains($queries, 'scoring_sessions'))->toBeFalse();
    expect(str_contains($queries, 'score_events'))->toBeFalse();
    expect(str_contains($queries, 'match_roster_players'))->toBeFalse();
});

test('resuming public scoreboards restores normal public viewing', function () {
    [$meet, $match] = loadControlLiveMatch();
    suspendScoreboards();

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertInertia(fn (AssertableInertia $page) => $page->component('portal/scoreboard-suspended'));

    Setting::current()->forceFill(['live_scoreboards_suspended' => false])->save();
    Setting::forgetLoadControlsCache();

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('portal/scoreboard')->where('session.score_a', 12));

    $this->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard/poll")
        ->assertOk()
        ->assertJsonPath('session.score_a', 12);
});

test('suspending public scoreboards does not disable authenticated ICT scoring', function () {
    [$meet, $match, $session] = loadControlLiveMatch();
    $admin = User::factory()->admin()->create();
    suspendScoreboards();

    // The ICT operator console + its poll keep working.
    $this->actingAs($admin)->get("/matches/{$match->id}/scoreboard")->assertOk();
    $this->actingAs($admin)->getJson("/matches/{$match->id}/scoring-session")->assertOk();

    // And a scoring mutation still lands.
    $this->actingAs($admin)
        ->patch("/scoring-sessions/{$session->id}/score", ['type' => 'point', 'side' => 'a', 'delta' => 3])
        ->assertSessionHasNoErrors();

    expect($session->fresh()->score_a)->toBe(15);
});

test('suspend and resume never touch scoring data', function () {
    [, $match, $session] = loadControlLiveMatch();
    $admin = User::factory()->admin()->create();

    ScoreEvent::factory()->count(3)->create(['scoring_session_id' => $session->id]);
    $sessionCount = ScoringSession::count();
    $eventCount = ScoreEvent::count();

    $this->actingAs($admin)->post('/system/load-controls/suspend-scoreboards');
    $this->actingAs($admin)->post('/system/load-controls/resume-scoreboards');

    expect(ScoringSession::count())->toBe($sessionCount)
        ->and(ScoreEvent::count())->toBe($eventCount)
        ->and($session->fresh())->not->toBeNull();
});

// ============================================================
// C / K. Disconnect sessions
// ============================================================

test('a System Administrator can disconnect inactive sessions and it is audited', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    seedSession('stale-1', $other->id, now()->subMinutes(30)->getTimestamp());
    seedSession('fresh-1', $other->id, now()->subMinute()->getTimestamp());
    seedSession('guest-stale', null, now()->subMinutes(30)->getTimestamp());

    $this->actingAs($admin)->post('/system/load-controls/disconnect-inactive')->assertRedirect();

    expect(DB::table('sessions')->where('id', 'stale-1')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'fresh-1')->exists())->toBeTrue()
        // Guest sessions are not "users" — left alone.
        ->and(DB::table('sessions')->where('id', 'guest-stale')->exists())->toBeTrue();

    $audit = AuditLog::where('action', 'load_control.sessions_disconnected')->first();
    expect($audit->context['mode'])->toBe('inactive')
        ->and($audit->context['sessions_invalidated'])->toBe(1)
        ->and($audit->context['administrator_id'])->toBe($admin->id);
});

test('a System Administrator can disconnect every non-admin session', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $coach = User::factory()->create(['role' => 'coach']);

    seedSession('coach-sess', $coach->id, now()->getTimestamp());
    seedSession('admin-sess', $otherAdmin->id, now()->subMinutes(30)->getTimestamp());

    $this->actingAs($admin)->post('/system/load-controls/disconnect-non-admin')->assertRedirect();

    expect(DB::table('sessions')->where('id', 'coach-sess')->exists())->toBeFalse()
        // Another administrator's session (even a stale one) is preserved.
        ->and(DB::table('sessions')->where('id', 'admin-sess')->exists())->toBeTrue();
});

test('disconnecting sessions leaves unrelated cache, queue and lock data intact', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->admin()->create();

    Cache::put('unrelated-key', 'keep-me', now()->addHour());
    DB::table('jobs')->insert([
        'queue' => 'default', 'payload' => '{}', 'attempts' => 0,
        'reserved_at' => null, 'available_at' => now()->getTimestamp(), 'created_at' => now()->getTimestamp(),
    ]);
    seedSession('x', User::factory()->create()->id, now()->subMinutes(30)->getTimestamp());

    $this->actingAs($admin)->post('/system/load-controls/disconnect-inactive');
    $this->actingAs($admin)->post('/system/load-controls/disconnect-non-admin');

    expect(Cache::get('unrelated-key'))->toBe('keep-me')
        ->and(DB::table('jobs')->count())->toBe(1);
});

test('the acting administrator is never disconnected by a non-admin sweep', function () {
    config(['session.driver' => 'database']);
    $admin = User::factory()->admin()->create();

    // The admin's own (stale) session must survive both sweeps: it is an
    // admin session, and the current session id is excluded.
    seedSession('my-own', $admin->id, now()->subMinutes(45)->getTimestamp());

    $this->actingAs($admin)->post('/system/load-controls/disconnect-non-admin');
    $this->actingAs($admin)->post('/system/load-controls/disconnect-inactive');

    expect(DB::table('sessions')->where('id', 'my-own')->exists())->toBeTrue();
});

test('non-administrators cannot disconnect sessions', function () {
    $organizer = User::factory()->organizer()->create();

    $this->actingAs($organizer)->post('/system/load-controls/disconnect-inactive')->assertForbidden();
    $this->actingAs($organizer)->post('/system/load-controls/disconnect-non-admin')->assertForbidden();
});

// ============================================================
// D. Inactivity expiry middleware
// ============================================================

test('inactivity expiry is off by default — an idle session is untouched', function () {
    $admin = User::factory()->admin()->create();

    // Feature not enabled: even a very stale marker does not sign the user out.
    $this->actingAs($admin)
        ->withSession(['last_activity_at' => now()->subHours(3)->getTimestamp()])
        ->get('/dashboard')
        ->assertOk();

    $this->assertAuthenticated();

    // And the middleware does not even stamp the marker while disabled.
    expect(session('last_activity_at'))->toBe(now()->subHours(3)->getTimestamp());
});

test('an authenticated user idle past the timeout is signed out on the next request', function () {
    enableInactivityExpiry();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['last_activity_at' => now()->subMinutes(6)->getTimestamp()])
        ->get('/dashboard')
        ->assertRedirect('/login');

    $this->assertGuest();
});

test('genuine activity within the timeout keeps the session valid', function () {
    enableInactivityExpiry();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['last_activity_at' => now()->subMinutes(2)->getTimestamp()])
        ->get('/dashboard')
        ->assertOk();

    $this->assertAuthenticated();
});

test('an idle Inertia request is redirected cleanly, never a 500 or 422', function () {
    enableInactivityExpiry();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)
        ->withSession(['last_activity_at' => now()->subMinutes(10)->getTimestamp()])
        ->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => '1'])
        ->get('/dashboard');

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toContain('/login');
});

test('background public scoreboard polling does not extend an authenticated session', function () {
    enableInactivityExpiry();
    [$meet, $match] = loadControlLiveMatch();
    $admin = User::factory()->admin()->create();

    // A near-stale session hits only the passive public poll: it must
    // neither be logged out by it nor have its activity marker refreshed.
    $this->actingAs($admin)
        ->withSession(['last_activity_at' => now()->subMinutes(4)->getTimestamp()])
        ->get("/meets/{$meet->id}/matches/{$match->id}/scoreboard/poll")
        ->assertOk();

    expect(session('last_activity_at'))->toBe(now()->subMinutes(4)->getTimestamp());

    // The very next genuine request, now past the window, is signed out —
    // the poll bought it no extra time.
    $this->actingAs($admin)
        ->withSession(['last_activity_at' => now()->subMinutes(6)->getTimestamp()])
        ->get('/dashboard')
        ->assertRedirect('/login');
});

// ============================================================
// E. Settings validation
// ============================================================

test('the inactivity timeout cannot be set below five minutes', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/system-settings')
        ->put('/system-settings', systemSettingsPayload(['authenticated_inactivity_timeout_minutes' => 2]))
        ->assertSessionHasErrors('authenticated_inactivity_timeout_minutes');

    $this->actingAs($admin)
        ->put('/system-settings', systemSettingsPayload([
            'authenticated_inactivity_expiry_enabled' => true,
            'authenticated_inactivity_timeout_minutes' => 15,
        ]))
        ->assertSessionHasNoErrors();

    expect(Setting::current()->fresh())
        ->authenticated_inactivity_timeout_minutes->toBe(15)
        ->authenticated_inactivity_expiry_enabled->toBeTrue();
});

test('only a System Administrator may change the inactivity timeout', function () {
    $organizer = User::factory()->organizer()->create();

    $this->actingAs($organizer)
        ->put('/system-settings', systemSettingsPayload(['authenticated_inactivity_timeout_minutes' => 15]))
        ->assertForbidden();
});

/**
 * A minimal valid System Settings payload — the request marks most
 * fields optional, but `recaptcha_enabled` / `email_verification_enabled`
 * are `required`.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function systemSettingsPayload(array $overrides = []): array
{
    return array_merge([
        'recaptcha_enabled' => false,
        'email_verification_enabled' => false,
    ], $overrides);
}
