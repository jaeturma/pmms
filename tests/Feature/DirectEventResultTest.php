<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMedalConfig;
use App\Models\EventResult;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\MedalTallyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function directResultContext(): array
{
    $meet = Meet::factory()->active()->published()->create();
    $event = Event::factory()->team()->create();
    $meet->events()->attach($event);
    EventMedalConfig::query()->create([
        'event_id' => $event->id, 'awards_medals' => true, 'award_type' => 'TEAM',
        'physical_quantity_mode' => 'FIXED', 'gold_physical_quantity' => 12,
        'silver_physical_quantity' => 12, 'bronze_physical_quantity' => 12,
        'gold_tally_quantity' => 1, 'silver_tally_quantity' => 1, 'bronze_tally_quantity' => 1,
    ]);
    $delegations = Delegation::factory()->count(3)->approved()->create(['meet_id' => $meet->id]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $event->sport_id, 'active' => true]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id, 'user_id' => $ict->id,
        'role' => MeetSportAssignmentRole::TournamentICT, 'status' => MeetSportAssignmentStatus::Active,
    ]);
    $secretariat = User::factory()->create();
    $team = ManagementTeam::factory()->create([
        'meet_id' => $meet->id, 'team_type' => ManagementTeamType::MeetManagement,
        'source_code' => 'EVENT_SECRETARIAT', 'status' => ManagementTeamStatus::Active,
    ]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id, 'user_id' => $secretariat->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return compact('meet', 'event', 'delegations', 'ict', 'secretariat');
}

test('ICT submits a direct Event Result and Secretariat posts each medal exactly once on acceptance', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    ['meet' => $meet, 'event' => $event, 'delegations' => $delegations, 'ict' => $ict, 'secretariat' => $secretariat] = directResultContext();

    $this->actingAs($ict)->get('/results')->assertOk()
        ->assertInertia(fn ($page) => $page->where('canDirectResult', true)
            ->has('delegationOptions', 3)
            ->where('eventOptionsByMeet.0.id', $event->id));

    $this->actingAs($ict)->post('/results/direct', [
        'event_id' => $event->id,
        'gold_delegation_id' => $delegations[0]->id,
        'silver_delegation_id' => $delegations[1]->id,
        'bronze_delegation_id' => $delegations[2]->id,
        'evidence' => UploadedFile::fake()->image('result.jpg'),
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    $result = EventResult::query()->sole();
    expect($result->status)->toBe(ResultStatus::Submitted)
        ->and($result->match_id)->toBeNull()
        ->and($result->event_schedule_id)->toBeNull()
        ->and($result->placements()->pluck('delegation_id')->all())->toBe($delegations->modelKeys())
        ->and(Entry::query()->count())->toBe(0)
        ->and(TeamEntry::query()->count())->toBe(0)
        ->and(collect(app(MedalTallyService::class)->standings($meet->id)['districts'])->sum('total'))->toBe(0);

    $this->actingAs($secretariat)->post(route('results.event-secretariat.validate', $result))
        ->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Validated)
        ->and(collect(app(MedalTallyService::class)->standings($meet->id)['districts'])->sum('total'))->toBe(0);

    $this->actingAs($secretariat)->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)
        ->and($result->medalAwards()->count())->toBe(3);
    $totals = collect(app(MedalTallyService::class)->standings($meet->id)['districts']);
    expect($totals->sum('gold'))->toBe(1)->and($totals->sum('silver'))->toBe(1)->and($totals->sum('bronze'))->toBe(1);

    $this->actingAs($secretariat)->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->medalAwards()->count())->toBe(3)
        ->and(AuditLog::query()->where('action', 'result.made_official')->where('auditable_id', $result->id)->count())->toBe(1);
    $this->get("/meets/{$meet->id}/results")->assertOk()->assertSee($delegations[0]->registrantName());
});

test('direct Event Result rejects duplicate Delegations and missing evidence', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    ['event' => $event, 'delegations' => $delegations, 'ict' => $ict] = directResultContext();

    $this->actingAs($ict)->post('/results/direct', [
        'event_id' => $event->id,
        'gold_delegation_id' => $delegations[0]->id,
        'silver_delegation_id' => $delegations[0]->id,
        'bronze_delegation_id' => $delegations[2]->id,
    ])->assertSessionHasErrors(['gold_delegation_id', 'silver_delegation_id', 'evidence']);
});
