<?php

use App\Enums\DelegationStatus;
use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\School;
use App\Models\ScoreEvent;
use App\Models\ScoringSession;
use App\Services\MedalTallyService;
use Database\Seeders\DdopaaDemoSeeder;
use Database\Seeders\DdopaaLiveScoringSeeder;
use Database\Seeders\DdopaaReferenceSeeder;
use Database\Seeders\DdopaaResultsSeeder;
use Database\Seeders\DdopaaStandardSeeder;
use Database\Seeders\DdopaaStandardTierSeeder;
use Database\Seeders\DivisionRegistrySeeder;
use Database\Seeders\PerformanceBenchmarkSeeder;
use Database\Seeders\SportsCatalogSeeder;

/**
 * Every DdOPAA seeder looks up real `District`/`Sport` rows by name
 * (`firstOrFail`) — `RefreshDatabase` only migrates the schema, it
 * doesn't run `DatabaseSeeder`, so these catalog seeders (already
 * idempotent via `firstOrCreate`) must run first in every test below.
 */
function seedDdopaaCatalogPrerequisites(): void
{
    app(DivisionRegistrySeeder::class)->run();
    app(SportsCatalogSeeder::class)->run();
}

/**
 * WP6 — Testing & Seeding Safety. Proves the DdOPAA 2025 reference
 * dataset's integrity and safety automatically (Part 13's own invariant
 * list), not just by the manual `tinker` verification every earlier WP
 * in this initiative relied on. Runs the real seeder classes against
 * the test database (`local`/`testing`, per phpunit.xml's sqlite
 * `:memory:` connection) rather than reconstructing their output with
 * factories, since the point is proving what the actual seeders produce.
 */
function seedDdopaaReferenceAndStandard(): Meet
{
    seedDdopaaCatalogPrerequisites();
    app(DdopaaReferenceSeeder::class)->run();
    app(DdopaaStandardSeeder::class)->run();

    return Meet::query()->where('name', 'DdOPAA Meet 2025')->firstOrFail();
}

function seedDdopaaStandardTier(): Meet
{
    seedDdopaaCatalogPrerequisites();
    app(DdopaaStandardTierSeeder::class)->run();

    return Meet::query()->where('name', 'DdOPAA Meet 2025')->firstOrFail();
}

/**
 * @return array<string, int>
 */
function ddopaaCountsSnapshot(Meet $meet): array
{
    return [
        'delegations' => Delegation::query()->where('meet_id', $meet->id)->count(),
        'athletes' => Athlete::query()->where('lrn', 'like', '941%')->count(),
        'confirmed_entries' => Entry::query()->where('status', 'confirmed')
            ->whereHas('delegation', fn ($q) => $q->where('meet_id', $meet->id))->count(),
        'results' => EventResult::query()->where('meet_id', $meet->id)->count(),
        'placements' => ResultPlacement::query()
            ->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id))->count(),
        'matches' => EventMatch::query()->where('meet_id', $meet->id)->count(),
    ];
}

test('the standard tier registers all 11 real Davao de Oro municipality delegations, approved', function () {
    $meet = seedDdopaaReferenceAndStandard();

    $delegations = Delegation::query()->where('meet_id', $meet->id)->with('district')->get();

    expect($delegations)->toHaveCount(11)
        ->and($delegations->pluck('status')->unique()->all())->toBe([DelegationStatus::Approved])
        ->and($delegations->pluck('district.name')->sort()->values()->all())->toBe([
            'Compostela', 'Laak', 'Mabini', 'Maco', 'Maragusan', 'Mawab',
            'Monkayo', 'Montevista', 'Nabunturan', 'New Bataan', 'Pantukan',
        ]);
});

test('every seeded athlete\'s school is within their own delegation\'s registering municipality', function () {
    $meet = seedDdopaaReferenceAndStandard();

    $athletes = Athlete::query()
        ->whereHas('delegation', fn ($q) => $q->where('meet_id', $meet->id))
        ->with(['school', 'delegation'])
        ->get();

    $mismatches = $athletes->filter(
        fn (Athlete $athlete) => $athlete->school->district_id !== $athlete->delegation->district_id,
    );

    expect($athletes)->not->toBeEmpty()
        ->and($mismatches)->toHaveCount(0);
});

test('no seeded school ever crosses municipalities, even after the standard-tier seeder re-runs', function () {
    $meet = seedDdopaaReferenceAndStandard();
    $before = School::query()->where('school_id_code', 'like', '941%')->pluck('district_id', 'id');

    app(DdopaaReferenceSeeder::class)->run();
    app(DdopaaStandardSeeder::class)->run();

    $after = School::query()->where('school_id_code', 'like', '941%')->pluck('district_id', 'id');

    expect($after->all())->toBe($before->all())
        ->and($meet->fresh())->not->toBeNull();
});

test('no duplicate approved delegation per municipality per meet, even after a repeat run', function () {
    $meet = seedDdopaaReferenceAndStandard();

    app(DdopaaReferenceSeeder::class)->run();
    app(DdopaaStandardSeeder::class)->run();

    $duplicateMunicipalities = Delegation::query()
        ->where('meet_id', $meet->id)
        ->select('district_id')
        ->groupBy('district_id')
        ->havingRaw('count(*) > 1')
        ->count();

    expect($duplicateMunicipalities)->toBe(0)
        ->and(Delegation::query()->where('meet_id', $meet->id)->count())->toBe(11);
});

test('every seeded medal placement traces to a validated result, and municipality totals equal their own schools\' summed totals', function () {
    $meet = seedDdopaaStandardTier();

    $unvalidatedPlacements = ResultPlacement::query()
        ->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id)->where('status', '!=', ResultStatus::Validated->value))
        ->count();

    $tally = app(MedalTallyService::class)->standings($meet->id);
    $schoolTotalsByDistrict = collect($tally['schools'])->groupBy('district')->map(fn ($rows) => $rows->sum('total'));

    $mismatchedDistricts = collect($tally['districts'])
        ->filter(fn (array $row) => $schoolTotalsByDistrict->get($row['district'], 0) !== $row['total']);

    expect($unvalidatedPlacements)->toBe(0)
        ->and($tally['districts'])->not->toBeEmpty()
        ->and($mismatchedDistricts)->toHaveCount(0);
});

test('an encoded-but-unvalidated result never appears in the medal tally', function () {
    $meet = seedDdopaaStandardTier();
    $before = app(MedalTallyService::class)->standings($meet->id);

    $event = Event::factory()->create();
    $meet->events()->syncWithoutDetaching([$event->id]);

    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $entry = Entry::factory()->confirmed()->create([
        'athlete_id' => $athlete->id,
        'delegation_id' => $delegation->id,
        'event_id' => $event->id,
    ]);

    $result = EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => $entry->id, 'rank' => 1]);

    expect($result->status)->toBe(ResultStatus::Encoded)
        ->and(app(MedalTallyService::class)->standings($meet->id))->toEqual($before);
});

test('seeding live scoring samples never creates or touches EventResult/ResultPlacement', function () {
    seedDdopaaCatalogPrerequisites();
    app(DdopaaReferenceSeeder::class)->run();
    app(DdopaaStandardSeeder::class)->run();
    app(DdopaaResultsSeeder::class)->run();

    $meet = Meet::query()->where('name', 'DdOPAA Meet 2025')->firstOrFail();
    $resultsBefore = EventResult::query()->where('meet_id', $meet->id)->count();
    $placementsBefore = ResultPlacement::query()->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id))->count();

    expect($resultsBefore)->toBeGreaterThan(0);

    app(DdopaaLiveScoringSeeder::class)->run();

    expect(EventMatch::query()->where('meet_id', $meet->id)->count())->toBeGreaterThan(0)
        ->and(ScoringSession::query()->count())->toBeGreaterThan(0)
        ->and(ScoreEvent::query()->count())->toBeGreaterThan(0)
        ->and(EventResult::query()->where('meet_id', $meet->id)->count())->toBe($resultsBefore)
        ->and(ResultPlacement::query()->whereHas('result', fn ($q) => $q->where('meet_id', $meet->id))->count())->toBe($placementsBefore);
});

test('the standard-tier command is idempotent — running it twice produces identical row counts', function () {
    $meet = seedDdopaaStandardTier();
    $first = ddopaaCountsSnapshot($meet);

    app(DdopaaStandardTierSeeder::class)->run();
    $second = ddopaaCountsSnapshot($meet);

    expect($second)->toBe($first)
        ->and($first['delegations'])->toBe(11)
        ->and($first['athletes'])->toBe(531);
});

test('the demo-tier command is idempotent — running it twice produces identical row counts', function () {
    seedDdopaaCatalogPrerequisites();
    app(DdopaaDemoSeeder::class)->run();
    $firstAthletes = Athlete::query()->where('lrn', 'like', '942%')->count();
    $firstEntries = Entry::query()->where('status', 'confirmed')
        ->whereHas('athlete', fn ($q) => $q->where('lrn', 'like', '942%'))->count();

    app(DdopaaDemoSeeder::class)->run();

    expect(Athlete::query()->where('lrn', 'like', '942%')->count())->toBe($firstAthletes)
        ->and(Entry::query()->where('status', 'confirmed')->whereHas('athlete', fn ($q) => $q->where('lrn', 'like', '942%'))->count())->toBe($firstEntries)
        ->and($firstAthletes)->toBe(18);
});

test('the load-test tier command is idempotent — running it twice produces identical row counts', function () {
    app(PerformanceBenchmarkSeeder::class)->run();
    $meet = Meet::query()->where('name', 'Sample Performance Benchmark Meet')->firstOrFail();
    $firstDelegations = Delegation::query()->where('meet_id', $meet->id)->count();
    $firstAthletes = Athlete::query()->where('lrn', 'like', '9500%')->count();

    app(PerformanceBenchmarkSeeder::class)->run();

    expect(Delegation::query()->where('meet_id', $meet->id)->count())->toBe($firstDelegations)
        ->and(Athlete::query()->where('lrn', 'like', '9500%')->count())->toBe($firstAthletes)
        ->and($firstDelegations)->toBe(11);
});

test('DdopaaReferenceSeeder refuses to seed outside local/testing', function () {
    app()['env'] = 'production';

    app(DdopaaReferenceSeeder::class)->run();

    expect(Meet::query()->where('name', 'DdOPAA Meet 2025')->exists())->toBeFalse();
});

test('DdopaaStandardSeeder refuses to seed outside local/testing', function () {
    seedDdopaaCatalogPrerequisites();
    app(DdopaaReferenceSeeder::class)->run();

    app()['env'] = 'production';

    app(DdopaaStandardSeeder::class)->run();

    expect(Delegation::query()->count())->toBe(0);
});

test('DdopaaResultsSeeder refuses to seed outside local/testing', function () {
    seedDdopaaCatalogPrerequisites();
    app(DdopaaDemoSeeder::class)->run();

    app()['env'] = 'production';

    app(DdopaaResultsSeeder::class)->run();

    expect(EventResult::query()->count())->toBe(0);
});

test('DdopaaLiveScoringSeeder refuses to seed outside local/testing', function () {
    seedDdopaaCatalogPrerequisites();
    app(DdopaaDemoSeeder::class)->run();

    app()['env'] = 'production';

    app(DdopaaLiveScoringSeeder::class)->run();

    expect(EventMatch::query()->count())->toBe(0);
});

test('DdopaaDemoSeeder refuses to seed outside local/testing', function () {
    app()['env'] = 'production';

    app(DdopaaDemoSeeder::class)->run();

    expect(Meet::query()->where('name', 'DdOPAA Meet 2025')->exists())->toBeFalse()
        ->and(Athlete::query()->where('lrn', 'like', '942%')->exists())->toBeFalse();
});

test('DdopaaStandardTierSeeder refuses to seed outside local/testing', function () {
    app()['env'] = 'production';

    app(DdopaaStandardTierSeeder::class)->run();

    expect(Meet::query()->where('name', 'DdOPAA Meet 2025')->exists())->toBeFalse();
});
