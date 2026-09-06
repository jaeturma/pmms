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
        'gold_count' => 1, 'silver_count' => 1, 'bronze_count' => 1,
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

test('accepted medal results keep their public document preview and do not populate non-medal standings', function () {
    $this->withoutVite();
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $this->actingAs($context['ict'])->post('/results/direct', directPayload($context))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $attachment = $result->attachments()->sole();
    $url = route('public.result-document', [$result, $attachment]);
    auth()->logout();
    $this->get(route('public.sport-event', ['event' => $result->event_id, 'meet_id' => $result->meet_id]))
        ->assertInertia(fn ($page) => $page->has('standings', 0)->has('results', 1)
            ->where('results.0.documents.0.url', $url));
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    $attachment->update(['is_current' => false]);
    $this->get($url)->assertNotFound();
    $attachment->update(['is_current' => true]);
    $context['meet']->forceFill(['is_published' => false])->save();
    $this->get($url)->assertNotFound();
    $context['meet']->forceFill(['is_published' => true])->save();
    $result->forceFill(['status' => ResultStatus::Reopened])->save();
    $this->get($url)->assertNotFound();
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
    $this->get("/meets/{$meet->id}/results")->assertInertia(fn ($page) => $page->has('results', 1)
        ->where('results.0.placements.0.mark', '56.81 seconds')
        ->where('results.0.placements.0.medal', 'gold')
        ->where('results.0.placements.1.medal', 'silver')
        ->where('results.0.placements.2.medal', 'bronze'))
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

test('a single participant result defaults to no medals and is auto-accepted', function () {
    $this->withoutVite();
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $this->actingAs($context['ict'])->get('/results/submit')->assertOk();
    $this->post('/results/direct', [
        'event_id' => $context['event']->id,
        'gold_delegation_id' => $context['delegations'][0]->id,
        'gold_mark' => '12.45 seconds',
        'evidence' => UploadedFile::fake()->image('single.png'),
    ])->assertRedirect('/results')->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    expect($result->placements()->count())->toBe(1)
        ->and($result->placements()->sole()->tally_quantity)->toBe(0)
        ->and($result->fresh()->status)->toBe(ResultStatus::Official)
        ->and($result->fresh()->official_by)->not->toBeNull()
        ->and($result->medalAwards()->count())->toBe(0);
    $document = $result->attachments()->sole();
    $eventUrl = route('public.sport-event', ['event' => $result->event_id, 'meet_id' => $result->meet_id]);
    $documentUrl = route('public.result-document', [$result, $document]);
    auth()->logout();
    // Auto-accepted: the standing is immediately public on its Sports Event
    // page, but never on the public medal-results page.
    $this->get($eventUrl)->assertInertia(fn ($page) => $page
        ->component('portal/sport-event')->has('standings', 1)
        ->where('standings.0.mark', '12.45 seconds')->where('standings.0.medal', null)
        ->has('results', 0));
    // A non-medal standing carries no public evidence document.
    $this->get($documentUrl)->assertNotFound();
    $this->get("/meets/{$result->meet_id}/results")->assertInertia(fn ($page) => $page->has('results', 0)->has('sportOptions', 0));
    // Re-accepting is idempotent.
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official);
    auth()->logout();
    $context['meet']->forceFill(['is_published' => false])->save();
    $this->get($documentUrl)->assertNotFound();
    $this->get($eventUrl)->assertNotFound();
    $context['meet']->forceFill(['is_published' => true])->save();
    // Returning it for correction pulls it back out of the public portal.
    $result->forceFill(['status' => ResultStatus::Reopened])->save();
    $this->get($documentUrl)->assertNotFound();
    $this->get($eventUrl)->assertInertia(fn ($page) => $page->has('standings', 0)->has('results', 0));
});

test('ICT requests a correction on an official result, admin approves, ICT moves it to another event and resubmits, admin re-accepts and medals recount', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    ['meet' => $meet, 'ict' => $ict, 'secretariat' => $secretariat, 'delegations' => $delegations] = $context;

    $otherEvent = Event::factory()->team()->create(['sport_id' => $context['event']->sport_id]);
    $meet->events()->attach($otherEvent);
    EventMedalConfig::query()->create([
        'event_id' => $otherEvent->id, 'awards_medals' => true, 'award_type' => 'TEAM',
        'physical_quantity_mode' => 'FIXED', 'gold_physical_quantity' => 12,
        'silver_physical_quantity' => 12, 'bronze_physical_quantity' => 12,
        'gold_tally_quantity' => 2, 'silver_tally_quantity' => 1, 'bronze_tally_quantity' => 1,
    ]);

    $this->actingAs($ict)->post('/results/direct', directPayload($context))->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $this->actingAs($secretariat)->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)->and($result->medalAwards()->count())->toBe(3);
    expect(collect(app(MedalTallyService::class)->standings($meet->id)['districts'])->sum('total'))->toBe(3);

    // A non-assigned ICT and a repeat request are both rejected.
    $this->actingAs(User::factory()->create(['role' => UserRole::TournamentICT]))
        ->post("/results/{$result->id}/request-correction", ['reason' => 'Not my sport'])->assertForbidden();

    $this->actingAs($ict)->post("/results/{$result->id}/request-correction", [
        'reason' => 'Wrong Sports Event — this belongs to the other team event.',
    ])->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)
        ->and($result->fresh()->correction_requested_at)->not->toBeNull()
        ->and($result->medalAwards()->count())->toBe(3);

    $this->actingAs($ict)->post("/results/{$result->id}/request-correction", ['reason' => 'again'])
        ->assertStatus(422);

    $this->actingAs($ict)->get('/results')->assertInertia(fn ($page) => $page
        ->where('results.data.0.can_request_correction', false)
        ->where('results.data.0.correction_request.reason', 'Wrong Sports Event — this belongs to the other team event.'));

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->post(route('results.reopen', $result), ['reason' => 'Approving ICT correction request'])
        ->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Reopened)
        ->and($result->fresh()->correction_requested_at)->toBeNull()
        ->and($result->medalAwards()->count())->toBe(0)
        ->and(collect(app(MedalTallyService::class)->standings($meet->id)['districts'])->sum('total'))->toBe(0);

    // Corrects both the Sports Event and the Gold delegation/count in one
    // resubmission — the real-world shape of a correction, not just a
    // relabeling.
    $this->actingAs($ict)->post(route('results.direct.update', $result), directPayload($context, [
        'event_id' => $otherEvent->id, 'evidence' => null,
        'gold_delegation_id' => $delegations[1]->id, 'bronze_count' => 4,
    ]))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->event_id)->toBe($otherEvent->id)
        ->and($result->fresh()->status)->toBe(ResultStatus::Submitted);

    $this->actingAs($secretariat)->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)
        ->and($result->medalAwards()->sum('tally_quantity'))->toBe(6)
        ->and($result->medalAwards()->where('medal_type', 'gold')->sole()->delegation_id)->toBe($delegations[1]->id)
        ->and($result->medalAwards()->where('medal_type', 'bronze')->sole()->delegation_id)->toBe($delegations[0]->id);
    $totals = collect(app(MedalTallyService::class)->standings($meet->id)['districts']);
    expect($totals->sum('total'))->toBe(6);
});

/**
 * Build a dynamic medal-rows payload. `$rows` are `[medalType, delegationIndex,
 * count]` (mark optional 4th) tuples resolved against `$delegations`.
 */
function medalRowsPayload(array $context, array $delegations, array $rows, array $overrides = []): array
{
    return array_replace([
        'event_id' => $context['event']->id,
        'result_type' => 'medal',
        'medal_placements' => collect($rows)->map(fn (array $row): array => [
            'medal_type' => $row[0],
            'delegation_id' => $delegations[$row[1]]->id,
            'count' => $row[2],
            'mark' => $row[3] ?? null,
        ])->all(),
        'evidence' => UploadedFile::fake()->image('medal-rows.png'),
    ], $overrides);
}

function districtTally(int $meetId): array
{
    $districts = collect(app(MedalTallyService::class)->standings($meetId)['districts']);

    return [
        'gold' => (int) $districts->sum('gold'),
        'silver' => (int) $districts->sum('silver'),
        'bronze' => (int) $districts->sum('bronze'),
        'total' => (int) $districts->sum('total'),
    ];
}

test('Direct Result accepts any medal combination and tallies every submitted row exactly, idempotently', function (array $rows, array $expected) {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $delegations = Delegation::factory()->count(5)->approved()->create(['meet_id' => $context['meet']->id]);
    $awardedRows = collect($rows)->filter(fn (array $row): bool => $row[2] > 0)->count();

    $this->actingAs($context['ict'])
        ->post('/results/direct', medalRowsPayload($context, $delegations->all(), $rows))
        ->assertRedirect()->assertSessionDoesntHaveErrors();

    $result = EventResult::query()->sole();
    expect($result->placements()->count())->toBe(count($rows))
        ->and($result->placements()->pluck('medal_type')->all())->toBe(collect($rows)->pluck(0)->all());

    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)
        // One award row per submitted medal row — never collapsed by shared
        // event / delegation / medal type.
        ->and($result->medalAwards()->count())->toBe($awardedRows);

    expect(districtTally($context['meet']->id))->toMatchArray($expected);

    // Repeated Accept must not duplicate the tally or the award rows.
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->medalAwards()->count())->toBe($awardedRows)
        ->and(districtTally($context['meet']->id))->toMatchArray($expected);
})->with([
    'Gold/Silver/Bronze (default 3 rows)' => [
        [['gold', 0, 1], ['silver', 1, 1], ['bronze', 2, 1]],
        ['gold' => 1, 'silver' => 1, 'bronze' => 1, 'total' => 3],
    ],
    'Gold/Silver/Bronze/Bronze (added 4th Bronze)' => [
        [['gold', 0, 1], ['silver', 1, 1], ['bronze', 2, 1], ['bronze', 3, 1]],
        ['gold' => 1, 'silver' => 1, 'bronze' => 2, 'total' => 4],
    ],
    'Gold/Silver/Silver/Bronze/Bronze' => [
        [['gold', 0, 1], ['silver', 1, 1], ['silver', 2, 1], ['bronze', 3, 1], ['bronze', 4, 1]],
        ['gold' => 1, 'silver' => 2, 'bronze' => 2, 'total' => 5],
    ],
    'Gold/Gold/Silver' => [
        [['gold', 0, 1], ['gold', 1, 1], ['silver', 2, 1]],
        ['gold' => 2, 'silver' => 1, 'bronze' => 0, 'total' => 3],
    ],
    'Gold/Gold/Gold (three delegations)' => [
        [['gold', 0, 1], ['gold', 1, 1], ['gold', 2, 1]],
        ['gold' => 3, 'silver' => 0, 'bronze' => 0, 'total' => 3],
    ],
    'same delegation repeated for the same medal type' => [
        [['gold', 0, 1], ['gold', 0, 1], ['gold', 0, 1]],
        ['gold' => 3, 'silver' => 0, 'bronze' => 0, 'total' => 3],
    ],
    'tally counts greater than one' => [
        [['gold', 0, 2], ['silver', 1, 3], ['bronze', 2, 1]],
        ['gold' => 2, 'silver' => 3, 'bronze' => 1, 'total' => 6],
    ],
    'zero-count row contributes nothing' => [
        [['gold', 0, 1], ['silver', 1, 0], ['bronze', 2, 1]],
        ['gold' => 1, 'silver' => 0, 'bronze' => 1, 'total' => 2],
    ],
]);

test('correcting an accepted Direct Result reconciles the medal tally instead of appending awards', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $context = directResultContext();
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $delegations = Delegation::factory()->count(4)->approved()->create(['meet_id' => $context['meet']->id])->all();

    $this->actingAs($context['ict'])
        ->post('/results/direct', medalRowsPayload($context, $delegations, [['gold', 0, 1], ['silver', 1, 1], ['bronze', 2, 1]]))
        ->assertSessionDoesntHaveErrors();
    $result = EventResult::query()->sole();
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->medalAwards()->count())->toBe(3)
        ->and(districtTally($context['meet']->id))->toMatchArray(['gold' => 1, 'silver' => 1, 'bronze' => 1, 'total' => 3]);

    // Reopen, resubmit a wholly different combination, re-accept.
    $this->actingAs($admin)->post(route('results.reopen', $result), ['reason' => 'Protest upheld'])->assertSessionDoesntHaveErrors();
    expect($result->fresh()->medalAwards()->count())->toBe(0);

    $this->actingAs($context['ict'])->post(
        route('results.direct.update', $result),
        medalRowsPayload($context, $delegations, [['gold', 0, 1], ['gold', 3, 1], ['silver', 1, 1], ['bronze', 2, 2]], ['evidence' => null]),
    )->assertSessionDoesntHaveErrors();
    $this->actingAs($context['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();

    expect($result->fresh()->medalAwards()->count())->toBe(4)
        ->and(districtTally($context['meet']->id))->toMatchArray(['gold' => 2, 'silver' => 1, 'bronze' => 2, 'total' => 5]);
});
