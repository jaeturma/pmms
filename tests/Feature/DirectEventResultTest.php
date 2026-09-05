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
use App\Services\MedalAwardService;
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

test('direct Event Result permits repeated Delegations but requires evidence', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    ['event' => $event, 'delegations' => $delegations, 'ict' => $ict] = directResultContext();

    $this->actingAs($ict)->post('/results/direct', [
        'event_id' => $event->id,
        'gold_delegation_id' => $delegations[0]->id,
        'silver_delegation_id' => $delegations[0]->id,
        'bronze_delegation_id' => $delegations[2]->id,
    ])->assertSessionHasErrors(['evidence'])->assertSessionDoesntHaveErrors(['gold_delegation_id', 'silver_delegation_id']);
});

function directPayload(array $context, array $overrides = []): array
{
    return array_replace([
        'event_id' => $context['event']->id,
        'gold_delegation_id' => $context['delegations'][0]->id,
        'silver_delegation_id' => $context['delegations'][0]->id,
        'bronze_delegation_id' => $context['delegations'][0]->id,
        'gold_mark' => '56.81 seconds', 'silver_mark' => '10.25 m', 'bronze_mark' => '98.5 points',
        'gold_count' => 1, 'silver_count' => 1, 'bronze_count' => 1,
        'evidence' => UploadedFile::fake()->image('official-sheet.png'),
    ], $overrides);
}

test('accepting submitted direct result awards same Delegation Gold Silver Bronze and immediately publishes', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    ['meet' => $meet, 'ict' => $ict, 'secretariat' => $secretariat, 'delegations' => $delegations] = $context;
    $this->actingAs($ict)->post('/results/direct', directPayload($context))->assertRedirect()->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    expect($result->medalAwards()->count())->toBe(0);
    $attachment = $result->attachments()->sole();
    Storage::disk('local')->assertExists($attachment->file->path);
    expect($result->placements()->orderBy('rank')->pluck('mark')->all())->toBe(['56.81 seconds', '10.25 m', '98.5 points']);
    expect($result->placements()->pluck('tally_quantity')->all())->toBe([1, 1, 1]);
    $this->get("/meets/{$meet->id}/results")->assertInertia(fn ($page) => $page->has('results', 0));
    $this->get("/meets/{$meet->id}/tally")->assertInertia(fn ($page) => $page->where('totals.total', 0));
    $this->actingAs($secretariat)->get('/results')->assertInertia(fn ($page) => $page->where('results.data.0.can_officialize', true));
    $this->get(route('results.attachments.download', [$result, $attachment]))->assertOk();
    $this->post(route('results.official', $result))->assertRedirect()->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)
        ->and($result->fresh()->official_by)->toBe($secretariat->id)
        ->and($result->fresh()->official_at)->not->toBeNull();
    foreach (['gold', 'silver', 'bronze'] as $medal) {
        $this->assertDatabaseHas('medal_awards', ['event_result_id' => $result->id, 'delegation_id' => $delegations[0]->id, 'medal_type' => $medal, 'tally_quantity' => 1, 'physical_quantity' => 12]);
    }
    $totals = collect(app(MedalTallyService::class)->standings($meet->id)['districts']);
    expect($totals->sum('gold'))->toBe(1)->and($totals->sum('silver'))->toBe(1)->and($totals->sum('bronze'))->toBe(1)->and($totals->sum('total'))->toBe(3);
    $this->get("/meets/{$meet->id}/results")->assertInertia(fn ($page) => $page->has('results', 1)->where('results.0.placements.0.mark', '56.81 seconds'))
        ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
    $this->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->medalAwards()->count())->toBe(3)
        ->and(collect(app(MedalTallyService::class)->standings($meet->id)['districts'])->sum('total'))->toBe(3);
    $this->get("/meets/{$meet->id}/tally")->assertInertia(fn ($page) => $page
        ->where('totals.gold', 1)->where('totals.silver', 1)->where('totals.bronze', 1)->where('totals.total', 3));
});

test('every active meet delegation is selectable independently and inactive or other meet delegations are rejected', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $active = Delegation::factory()->submitted()->create(['meet_id' => $context['meet']->id]);
    $inactive = Delegation::factory()->create(['meet_id' => $context['meet']->id]);
    $otherMeet = Delegation::factory()->approved()->create();
    $this->actingAs($context['ict'])->get('/results')->assertInertia(fn ($page) => $page->has('delegationOptions', 4)
        ->where('delegationOptions', fn ($options) => collect($options)->pluck('id')->contains($active->id)));
    foreach (['gold', 'silver', 'bronze'] as $medal) {
        foreach ([$inactive, $otherMeet] as $invalid) {
            $this->post('/results/direct', directPayload($context, [$medal.'_delegation_id' => $invalid->id]))->assertStatus(422);
        }
    }
    $this->post('/results/direct', directPayload($context, [
        'gold_delegation_id' => $active->id, 'silver_delegation_id' => $active->id, 'bronze_delegation_id' => $active->id,
    ]))->assertRedirect()->assertSessionDoesntHaveErrors();
    expect(EventResult::query()->sole()->placements()->pluck('delegation_id')->all())->toBe([$active->id, $active->id, $active->id]);
});

test('ICT cannot accept and historical direct records with null quantities can be reconciled safely', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $this->actingAs($context['ict'])->post('/results/direct', directPayload($context))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $this->post(route('results.official', $result))->assertForbidden();
    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)->and($result->medalAwards()->count())->toBe(0);
    $result->placements()->update(['tally_quantity' => null]);
    $result->forceFill(['status' => ResultStatus::Official])->save();
    $this->get("/meets/{$context['meet']->id}/results")->assertOk();
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->medalAwards()->count())->toBe(3)->and($result->medalAwards()->sum('tally_quantity'))->toBe(3);
});

test('explicit direct counts override zero or incomplete event tally defaults', function ($configuredQuantity) {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $context['event']->medalConfig->update([
        'gold_tally_quantity' => $configuredQuantity,
        'silver_tally_quantity' => $configuredQuantity,
        'bronze_tally_quantity' => $configuredQuantity,
    ]);
    $this->actingAs($context['ict'])->post('/results/direct', directPayload($context))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->medalAwards()->count())->toBe(3)->and($result->medalAwards()->sum('tally_quantity'))->toBe(3);
    expect($result->medalAwards()->sum('physical_quantity'))->toBe(36);
})->with(['zero' => [0], 'incomplete' => [null]]);

test('failed direct acceptance rolls back both status and partially created awards', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $this->actingAs($context['ict'])->post('/results/direct', directPayload($context))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $realService = new MedalAwardService;
    $this->mock(MedalAwardService::class)->shouldReceive('synchronize')->once()
        ->andReturnUsing(function ($locked, $actor) use ($realService) {
            $realService->synchronize($locked, $actor);
            throw new RuntimeException('Simulated snapshot failure');
        });
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertStatus(500);
    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)->and($result->medalAwards()->count())->toBe(0);
    expect(AuditLog::query()->where('action', 'result.made_official')->count())->toBe(0);
});

test('direct quantities are independent and zero or non medal placements never count', function (bool $awardsMedals) {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $context['event']->medalConfig->update(['awards_medals' => $awardsMedals]);
    $this->actingAs($context['ict'])->post('/results/direct', directPayload($context, ['gold_count' => 2, 'silver_count' => 3, 'bronze_count' => 0]))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $totals = collect(app(MedalTallyService::class)->standings($context['meet']->id)['districts']);
    expect($totals->sum('gold'))->toBe($awardsMedals ? 2 : 0)
        ->and($totals->sum('silver'))->toBe($awardsMedals ? 3 : 0)
        ->and($totals->sum('bronze'))->toBe(0)
        ->and($totals->sum('total'))->toBe($awardsMedals ? 5 : 0)
        ->and($result->medalAwards()->count())->toBe($awardsMedals ? 2 : 0);
})->with([true, false]);

test('reopen correction and cancellation replace and reverse accepted direct awards', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($context['ict'])->post('/results/direct', directPayload($context))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $fileId = $result->attachments()->sole()->file_upload_id;
    $this->actingAs($admin)->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $this->post(route('results.direct.update', $result), directPayload($context, ['evidence' => null]))->assertStatus(422);
    $this->post(route('results.reopen', $result), ['reason' => 'Correct Gold delegation'])->assertSessionDoesntHaveErrors();
    expect($result->medalAwards()->count())->toBe(0)
        ->and(collect(app(MedalTallyService::class)->standings($context['meet']->id)['districts'])->sum('total'))->toBe(0);
    $this->get("/meets/{$context['meet']->id}/results")->assertInertia(fn ($page) => $page->has('results', 0));
    $this->actingAs($context['ict'])->post(route('results.direct.update', $result), directPayload($context, [
        'gold_delegation_id' => $context['delegations'][1]->id, 'bronze_count' => 4, 'evidence' => null,
    ]))->assertRedirect()->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)
        ->and($result->attachments()->sole()->file_upload_id)->toBe($fileId);
    $this->actingAs($admin)->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $this->assertDatabaseMissing('medal_awards', ['event_result_id' => $result->id, 'delegation_id' => $context['delegations'][0]->id, 'medal_type' => 'gold']);
    $this->assertDatabaseHas('medal_awards', ['event_result_id' => $result->id, 'delegation_id' => $context['delegations'][1]->id, 'medal_type' => 'gold', 'tally_quantity' => 1]);
    expect(collect(app(MedalTallyService::class)->standings($context['meet']->id)['districts'])->sum('total'))->toBe(6);
    $this->post(route('results.cancel', $result), ['reason' => 'Cancelled event'])->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Cancelled)->and($result->medalAwards()->count())->toBe(0)
        ->and(collect(app(MedalTallyService::class)->standings($context['meet']->id)['districts'])->sum('total'))->toBe(0);
});
