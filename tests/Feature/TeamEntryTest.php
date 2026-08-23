<?php

use App\Enums\AgeDivision;
use App\Enums\EligibilityStatus;
use App\Enums\GenderCategory;
use App\Enums\MedicalClearanceStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MedicalClearance;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\MedalTallyService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function teamEntryContext(int $teamSize = 2, bool $medicalRequired = false): array
{
    $delegation = Delegation::factory()->create();
    $delegation->meet->forceFill(['medical_clearance_required' => $medicalRequired])->save();
    $event = Event::factory()->team()->create([
        'gender' => GenderCategory::Boys,
        'age_division' => AgeDivision::Secondary,
        'team_size' => $teamSize,
    ]);
    $delegation->meet->events()->attach($event);

    $athletes = collect(range(1, $teamSize))->map(function () use ($delegation, $medicalRequired): Athlete {
        $athlete = Athlete::factory()->create([
            'delegation_id' => $delegation->id,
            'school_id' => $delegation->school_id,
            'sex' => 'male',
            'grade_level' => 8,
        ]);
        EligibilityReview::factory()->create([
            'athlete_id' => $athlete->id,
            'meet_id' => $delegation->meet_id,
            'status' => EligibilityStatus::Approved,
        ]);
        if ($medicalRequired) {
            MedicalClearance::factory()->create([
                'athlete_id' => $athlete->id,
                'meet_id' => $delegation->meet_id,
                'status' => MedicalClearanceStatus::Cleared,
            ]);
        }

        return $athlete;
    });

    return [$delegation, $event, $athletes];
}

test('a team roster reuses athlete event entries without duplicating athletes or memberships', function () {
    [$delegation, $event, $athletes] = teamEntryContext();
    $admin = User::factory()->admin()->create();
    $payload = ['event_id' => $event->id, 'athlete_ids' => $athletes->pluck('id')->all()];

    $this->actingAs($admin)->post('/team-entries', $payload)->assertRedirect()->assertSessionDoesntHaveErrors();
    $this->actingAs($admin)->post('/team-entries', $payload)->assertRedirect()->assertSessionDoesntHaveErrors();

    $team = TeamEntry::query()->sole();
    expect($team->delegation_id)->toBe($delegation->id)
        ->and($team->members()->count())->toBe(2)
        ->and(Athlete::query()->count())->toBe(2)
        ->and($event->entries()->count())->toBe(2);
});

test('a below-minimum team can be saved as submitted but cannot be finalized', function () {
    [, $event, $athletes] = teamEntryContext(2);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => [$athletes->first()->id],
    ])->assertSessionDoesntHaveErrors();

    $this->actingAs($admin)->patch('/team-entries/'.TeamEntry::query()->sole()->id.'/confirm')
        ->assertSessionHasErrors('athlete_ids');
});

test('a team above the configured maximum is rejected', function () {
    [, $event, $athletes] = teamEntryContext(3);
    $event->forceFill(['team_size' => 2])->save();

    $this->actingAs(User::factory()->admin()->create())->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => $athletes->pluck('id')->all(),
    ])->assertSessionHasErrors('athlete_ids');
});

test('wrong-delegation and ineligible athletes are rejected from a team', function () {
    [, $event, $athletes] = teamEntryContext(2);
    $outsider = Athlete::factory()->create(['sex' => 'male', 'grade_level' => 8]);
    EligibilityReview::factory()->approved()->create(['athlete_id' => $outsider->id]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => [$athletes->first()->id, $outsider->id],
    ])->assertSessionHasErrors('athlete_ids');

    $athletes->last()->eligibilityReview->forceFill(['status' => EligibilityStatus::Pending])->save();
    $this->actingAs($admin)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => $athletes->pluck('id')->all(),
    ])->assertSessionHasErrors('athlete_ids');
});

test('medical clearance is enforced when the meet requires it', function () {
    [, $event, $athletes] = teamEntryContext(2, true);
    $athletes->last()->medicalClearance->forceFill(['status' => MedicalClearanceStatus::NotCleared])->save();

    $this->actingAs(User::factory()->admin()->create())->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => $athletes->pluck('id')->all(),
    ])->assertSessionHasErrors('athlete_ids');
});

test('finalizing a complete team locks the snapshot and confirms member entries', function () {
    [, $event, $athletes] = teamEntryContext();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => $athletes->pluck('id')->all(),
    ]);
    $team = TeamEntry::query()->sole();

    $this->actingAs($admin)->patch("/team-entries/{$team->id}/confirm")->assertSessionDoesntHaveErrors();
    expect($team->refresh()->isRosterLocked())->toBeTrue()
        ->and($team->members()->whereHas('entry', fn ($entry) => $entry->where('status', 'confirmed'))->count())->toBe(2);

    $this->actingAs($admin)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => [$athletes->first()->id],
    ])->assertSessionHasErrors('athlete_ids');
});

test('a team gold counts once in delegation standings and appears for every snapshotted member', function () {
    [$delegation, $event, $athletes] = teamEntryContext(4);
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => $athletes->pluck('id')->all(),
    ]);
    $team = TeamEntry::query()->sole();
    $this->actingAs($admin)->patch("/team-entries/{$team->id}/confirm");

    $result = EventResult::factory()->validated()->create([
        'meet_id' => $delegation->meet_id,
        'event_id' => $event->id,
    ]);
    $result->placements()->create([
        'entry_id' => $team->members()->firstOrFail()->entry_id,
        'team_entry_id' => $team->id,
        'rank' => 1,
    ]);

    $standings = app(MedalTallyService::class)->standings($delegation->meet_id);
    expect(collect($standings['districts'])->sum('gold'))->toBe(1);

    foreach ($athletes as $athlete) {
        $this->actingAs($admin)->get("/athletes/{$athlete->id}")
            ->assertInertia(fn ($page) => $page
                ->has('athlete.achievements', 1)
                ->where('athlete.achievements.0.medal', 'Gold')
                ->where('athlete.achievements.0.team', true));
    }
});
