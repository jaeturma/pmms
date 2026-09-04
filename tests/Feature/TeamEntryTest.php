<?php

use App\Enums\AgeDivision;
use App\Enums\EligibilityStatus;
use App\Enums\GenderCategory;
use App\Enums\MedicalClearanceStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MedicalClearance;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\SportRosterMember;
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
    $meetSport = MeetSport::factory()->create(['meet_id' => $delegation->meet_id, 'sport_id' => $event->sport_id]);

    $athletes = collect(range(1, $teamSize))->map(function () use ($delegation, $medicalRequired, $meetSport): Athlete {
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
        SportRosterMember::query()->create([
            'meet_sport_id' => $meetSport->id, 'delegation_id' => $delegation->id,
            'athlete_id' => $athlete->id, 'level' => 'secondary', 'gender' => 'boys',
        ]);

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

test('finalized team members remain editable without changing the delegation entry', function () {
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
    ])->assertSessionDoesntHaveErrors();

    expect($team->refresh()->members()->count())->toBe(1)
        ->and($team->status->value)->toBe('confirmed');
});

test('assigned ICT can update athletes on an official winning team entry without changing its tally', function () {
    [$delegation, $event, $athletes] = teamEntryContext();
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

    $replacement = Athlete::factory()->create([
        'delegation_id' => $delegation->id,
        'school_id' => $delegation->school_id,
        'sex' => 'male',
        'grade_level' => 8,
    ]);
    $meetSport = MeetSport::query()->where('meet_id', $delegation->meet_id)
        ->where('sport_id', $event->sport_id)->sole();
    SportRosterMember::query()->create([
        'meet_sport_id' => $meetSport->id,
        'delegation_id' => $delegation->id,
        'athlete_id' => $replacement->id,
        'level' => 'secondary',
        'gender' => 'boys',
    ]);
    $ict = User::factory()->create(['role' => \App\Enums\UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'user_id' => $ict->id,
        'meet_sport_id' => $meetSport->id,
        'role' => MeetSportAssignmentRole::TournamentICT,
        'status' => MeetSportAssignmentStatus::Active,
    ]);
    $goldBefore = collect(app(MedalTallyService::class)->standings($delegation->meet_id)['districts'])->sum('gold');

    $this->actingAs($ict)->get('/entries')->assertInertia(fn ($page) => $page
        ->where('teamEntries.0.id', $team->id)
        ->where('teamEntries.0.can_assign_after_posting', true));

    $this->actingAs($ict)->post('/team-entries', [
        'event_id' => $event->id,
        'athlete_ids' => [$athletes->last()->id, $replacement->id],
    ])->assertSessionDoesntHaveErrors();

    expect($team->members()->pluck('athlete_id')->all())
        ->toEqualCanonicalizing([$athletes->last()->id, $replacement->id])
        ->and($result->placements()->sole()->rank)->toBe(1)
        ->and(collect(app(MedalTallyService::class)->standings($delegation->meet_id)['districts'])->sum('gold'))->toBe($goldBefore);

    $this->actingAs(User::factory()->create(['role' => \App\Enums\UserRole::TournamentICT]))
        ->post('/team-entries', [
            'event_id' => $event->id,
            'athlete_ids' => $athletes->pluck('id')->all(),
        ])->assertForbidden();
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
