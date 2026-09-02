<?php

use App\Enums\Permission;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\DemoScenario;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use App\Services\DemoScenarioService;
use App\Services\MeetReadinessService;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

function demoScenarioPayload(array $overrides = []): array
{
    return [...[
        'request_token' => (string) Str::uuid(),
        'meet_id' => $overrides['meet_id'] ?? Meet::factory()->create()->id,
        'sport_id' => $overrides['sport_id'] ?? Sport::factory()->create()->id,
        'venue_id' => $overrides['venue_id'] ?? Venue::factory()->create()->id,
        'name' => 'Provincial Meet Presentation',
        'event_name' => 'Basketball Secondary Boys',
        'template' => 'head_to_head',
        'gender' => 'boys',
        'age_division' => 'secondary',
        'scheduled_date' => '2026-09-03',
        'starts_at' => '14:00',
        'ends_at' => '16:00',
        'side_a_label' => 'Demo Team A',
        'side_b_label' => 'Demo Team B',
    ], ...$overrides];
}

test('administrator can generate a structurally marked demo scenario using real competition models', function () {
    $admin = User::factory()->admin()->create();
    $scenario = app(DemoScenarioService::class)->generate(demoScenarioPayload(), $admin);

    expect($admin->hasPermission(Permission::DemoManage))->toBeTrue()
        ->and($scenario->events()->sole()->demo_scenario_id)->toBe($scenario->id)
        ->and($scenario->schedules()->sole()->event_id)->toBe($scenario->events()->sole()->id)
        ->and($scenario->matches()->sole()->event_schedule_id)->toBe($scenario->schedules()->sole()->id)
        ->and($scenario->results()->sole()->match_id)->toBe($scenario->matches()->sole()->id)
        ->and($scenario->matches()->sole()->scoringSessions()->count())->toBe(1);
});

test('unauthorized users cannot manage demo data', function () {
    $viewer = User::factory()->create(['role' => UserRole::Viewer]);

    $this->actingAs($viewer)->get(route('demo-data.index'))->assertForbidden();
    $this->actingAs($viewer)->post(route('demo-data.store'), demoScenarioPayload())->assertForbidden();
});

test('normal production scopes exclude demo events schedules matches and results', function () {
    $scenario = app(DemoScenarioService::class)->generate(demoScenarioPayload(), User::factory()->admin()->create());

    expect(Event::query()->real()->whereKey($scenario->events()->sole()->id)->doesntExist())->toBeTrue()
        ->and(EventSchedule::query()->real()->whereKey($scenario->schedules()->sole()->id)->doesntExist())->toBeTrue()
        ->and(EventMatch::query()->real()->whereKey($scenario->matches()->sole()->id)->doesntExist())->toBeTrue()
        ->and(EventResult::query()->real()->whereKey($scenario->results()->sole()->id)->doesntExist())->toBeTrue();
});

test('demo generation is idempotent for the same request token', function () {
    $admin = User::factory()->admin()->create();
    $payload = demoScenarioPayload();
    $first = app(DemoScenarioService::class)->generate($payload, $admin);
    $second = app(DemoScenarioService::class)->generate($payload, $admin);

    expect($second->id)->toBe($first->id)
        ->and(DemoScenario::query()->count())->toBe(1)
        ->and(EventMatch::query()->demo()->count())->toBe(1);
});

test('demo scoreboard uses the normal scoring write endpoint', function () {
    $admin = User::factory()->admin()->create();
    $scenario = app(DemoScenarioService::class)->generate(demoScenarioPayload(), $admin);
    $session = $scenario->matches()->sole()->scoringSessions()->sole();

    $this->actingAs($admin)->patch(route('scoring.score', $session), [
        'type' => 'point', 'side' => 'a', 'delta' => 2,
    ])->assertRedirect();

    expect($session->refresh()->score_a)->toBe(2);
});

test('removing one scenario deletes only its owned records and preserves shared master and other demo data', function () {
    $admin = User::factory()->admin()->create();
    $meet = Meet::factory()->create();
    $sport = Sport::factory()->create(['name' => 'Volleyball']);
    $venue = Venue::factory()->create();
    $first = app(DemoScenarioService::class)->generate(demoScenarioPayload(['meet_id' => $meet->id, 'sport_id' => $sport->id, 'venue_id' => $venue->id]), $admin);
    $second = app(DemoScenarioService::class)->generate(demoScenarioPayload(['meet_id' => $meet->id, 'sport_id' => $sport->id, 'venue_id' => $venue->id]), $admin);

    app(DemoScenarioService::class)->remove($first, $admin);

    expect(DemoScenario::query()->whereKey($first->id)->doesntExist())->toBeTrue()
        ->and(DemoScenario::query()->whereKey($second->id)->exists())->toBeTrue()
        ->and(Sport::query()->whereKey($sport->id)->exists())->toBeTrue()
        ->and(Venue::query()->whereKey($venue->id)->exists())->toBeTrue()
        ->and(Meet::query()->whereKey($meet->id)->exists())->toBeTrue()
        ->and(ScoringSession::query()->whereHas('match', fn ($q) => $q->where('demo_scenario_id', $second->id))->exists())->toBeTrue();
});

test('demo results are prevented from producing medal award snapshots even if marked official', function () {
    $admin = User::factory()->admin()->create();
    $scenario = app(DemoScenarioService::class)->generate(demoScenarioPayload(), $admin);
    $result = $scenario->results()->sole();
    $result->forceFill(['status' => ResultStatus::Official])->save();

    app(App\Services\MedalAwardService::class)->synchronize($result, $admin);

    expect($result->medalAwards()->count())->toBe(0);
});

test('demo events and schedules do not improve meet readiness', function () {
    $admin = User::factory()->admin()->create();
    $payload = demoScenarioPayload();
    $scenario = app(DemoScenarioService::class)->generate($payload, $admin);
    $snapshot = app(MeetReadinessService::class)->calculate(Meet::query()->findOrFail($payload['meet_id']));

    expect($snapshot['summary']['events_total'])->toBe(0)
        ->and(collect($snapshot['events']['data'])->pluck('id'))->not->toContain($scenario->events()->sole()->id);
});

test('normal public meet queries exclude live demo matches', function () {
    $admin = User::factory()->admin()->create();
    $meet = Meet::factory()->active()->published()->create();
    app(DemoScenarioService::class)->generate(demoScenarioPayload(['meet_id' => $meet->id]), $admin);

    $this->get(route('public.meet', $meet))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->where('liveMatches', []));
});

test('explicit demo preview visibly identifies every scenario as demonstration data', function () {
    $this->withoutVite();
    $admin = User::factory()->admin()->create();
    $scenario = app(DemoScenarioService::class)->generate(demoScenarioPayload(), $admin);

    $this->actingAs($admin)->get(route('demo-data.show', $scenario))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('system/demo-preview')
            ->where('scenario.id', $scenario->id)
            ->where('scenario.events.0.name', fn (string $name) => str_starts_with($name, 'DEMO')));
});
