<?php

use App\Enums\EligibilityStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use App\Models\SportRosterLimit;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\SportRosterService;
use Database\Seeders\DdOPAA2026MeetSeeder;
use Database\Seeders\SportsCatalogSeeder;
use Database\Seeders\SwimmingCompetitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('swimming roster screen is authenticated', function () {
    $this->get('/swimming/rosters')->assertRedirect('/login');
    $this->actingAs(User::factory()->admin()->create())->get('/swimming/rosters')
        ->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('entries/swimming-rosters')->has('rosters', 0));
});

function swimmingScope(string $level, string $gender, int $limit): array
{
    $meet = Meet::factory()->create(['medical_clearance_required' => false]);
    $sport = Sport::factory()->create(['code' => 'SWIMMING', 'name' => 'Swimming']);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);
    SportRosterLimit::query()->create(['meet_sport_id' => $meetSport->id, 'level' => $level, 'gender' => $gender, 'max_athletes' => $limit]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);

    return [$meet, $sport, $meetSport, $delegation];
}

test('swimming category roster limits are independent and reject overflow', function (string $level, string $gender, int $limit) {
    [, , $meetSport, $delegation] = swimmingScope($level, $gender, $limit);
    $sex = $gender === 'boys' ? 'male' : 'female';
    $grade = $level === 'elementary' ? 6 : 8;
    $athletes = Athlete::factory()->count($limit + 1)->create(['delegation_id' => $delegation->id, 'school_id' => $delegation->school_id, 'sex' => $sex, 'grade_level' => $grade]);
    $service = app(SportRosterService::class);
    $athletes->take($limit)->each(fn (Athlete $athlete) => $service->add($meetSport, $athlete));

    expect($meetSport->rosterMembers()->count())->toBe($limit);
    expect(fn () => $service->add($meetSport, $athletes->last()))->toThrow(\Illuminate\Validation\ValidationException::class);
})->with([
    'Elementary Boys 11' => ['elementary', 'boys', 11],
    'Elementary Girls 11' => ['elementary', 'girls', 11],
    'Secondary Boys 12' => ['secondary', 'boys', 12],
    'Secondary Girls 12' => ['secondary', 'girls', 12],
]);

test('one roster swimmer can enter multiple individual events and a relay', function () {
    [$meet, $sport, $meetSport, $delegation] = swimmingScope('secondary', 'boys', 12);
    $admin = User::factory()->admin()->create();
    $events = collect(['50m Freestyle', '100m Butterfly'])->map(fn (string $name) => Event::factory()->create([
        'sport_id' => $sport->id, 'name' => $name, 'gender' => 'boys', 'age_division' => 'secondary', 'is_team_event' => false, 'event_type' => 'INDIVIDUAL',
    ]));
    $relay = Event::factory()->team()->create([
        'sport_id' => $sport->id, 'name' => '4x50m Freestyle Relay', 'gender' => 'boys', 'age_division' => 'secondary',
        'event_type' => 'RELAY', 'team_size' => 4, 'relay_legs' => 4,
    ]);
    $meet->events()->syncWithoutDetaching([...$events->pluck('id')->all(), $relay->id]);
    $athletes = Athlete::factory()->count(4)->create(['delegation_id' => $delegation->id, 'school_id' => $delegation->school_id, 'sex' => 'male', 'grade_level' => 8]);
    foreach ($athletes as $athlete) {
        app(SportRosterService::class)->add($meetSport, $athlete);
        EligibilityReview::factory()->create(['athlete_id' => $athlete->id, 'meet_id' => $meet->id, 'status' => EligibilityStatus::Approved]);
    }

    $this->actingAs($admin)->post('/entries', ['athlete_id' => $athletes->first()->id, 'event_ids' => $events->pluck('id')->all()])->assertSessionDoesntHaveErrors();
    $this->actingAs($admin)->post('/team-entries', ['event_id' => $relay->id, 'athlete_ids' => $athletes->modelKeys()])->assertSessionDoesntHaveErrors();

    expect(Entry::query()->where('athlete_id', $athletes->first()->id)->count())->toBe(3)
        ->and(TeamEntry::query()->sole()->members()->orderBy('member_order')->pluck('member_order')->all())->toBe([1, 2, 3, 4]);

    $this->actingAs($admin)->post('/entries', ['athlete_id' => $athletes->first()->id, 'event_id' => $events->first()->id])->assertSessionHasErrors('event_id');
});

test('relay rejects fewer than four swimmers and non roster swimmers', function () {
    [$meet, $sport, $meetSport, $delegation] = swimmingScope('secondary', 'boys', 12);
    $relay = Event::factory()->team()->create(['sport_id' => $sport->id, 'gender' => 'boys', 'age_division' => 'secondary', 'event_type' => 'RELAY', 'team_size' => 4, 'relay_legs' => 4]);
    $meet->events()->attach($relay);
    $athletes = Athlete::factory()->count(4)->create(['delegation_id' => $delegation->id, 'school_id' => $delegation->school_id, 'sex' => 'male', 'grade_level' => 8]);
    foreach ($athletes as $athlete) {
        EligibilityReview::factory()->approved()->create(['athlete_id' => $athlete->id, 'meet_id' => $meet->id]);
    }
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/team-entries', ['event_id' => $relay->id, 'athlete_ids' => $athletes->take(3)->modelKeys()])->assertSessionHasErrors('athlete_ids');
    $this->actingAs($admin)->post('/team-entries', ['event_id' => $relay->id, 'athlete_ids' => [$athletes[0]->id, $athletes[0]->id, $athletes[1]->id, $athletes[2]->id]])->assertSessionHasErrors('athlete_ids.1');
    $athletes->take(3)->each(fn (Athlete $athlete) => app(SportRosterService::class)->add($meetSport, $athlete));
    $this->actingAs($admin)->post('/team-entries', ['event_id' => $relay->id, 'athlete_ids' => $athletes->modelKeys()])->assertSessionHasErrors('athlete_ids');
});

test('the official swimming program seeds all event numbers and relay metadata idempotently', function () {
    $this->seed(SportsCatalogSeeder::class);
    $this->seed(DdOPAA2026MeetSeeder::class);
    $this->seed(SwimmingCompetitionSeeder::class);
    $this->seed(SwimmingCompetitionSeeder::class);
    $sport = Sport::query()->where('code', 'SWIMMING')->sole();
    $events = Event::query()->whereBelongsTo($sport)->whereNotNull('event_no')->orderBy('event_no')->get();

    expect($events)->toHaveCount(72)
        ->and($events->pluck('event_no')->all())->toBe(range(1, 72))
        ->and($events->where('event_type', 'RELAY'))->toHaveCount(16)
        ->and($events->where('event_type', 'INDIVIDUAL'))->toHaveCount(56)
        ->and($events->where('event_type', 'RELAY')->every(fn (Event $event) => $event->relay_legs === 4 && $event->team_size === 4))->toBeTrue()
        ->and($events->firstWhere('event_no', 51)->distance_meters)->toBe(200)
        ->and($events->firstWhere('event_no', 51)->stroke)->toBe('FREESTYLE_RELAY');
    $meetSport = MeetSport::query()->whereBelongsTo(Meet::query()->where('name', 'DdOPAA Meet 2026')->sole())->whereBelongsTo($sport)->sole();
    expect($meetSport->rosterLimits()->pluck('max_athletes')->sort()->values()->all())->toBe([11, 11, 12, 12]);
});
