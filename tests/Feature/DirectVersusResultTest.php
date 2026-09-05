<?php

use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\EventResult;
use App\Models\AuditLog;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\User;
use App\Services\EventTeamStandingsService;
use App\Services\MedalTallyService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function versusPayload(array $c, array $overrides = []): array
{
    return array_replace([
        'event_id' => $c['event']->id, 'result_type' => 'versus', 'measurement_type' => 'score',
        'winner_delegation_id' => $c['delegations'][0]->id, 'loser_delegation_id' => $c['delegations'][1]->id,
        'winner_value' => '85', 'loser_value' => '74', 'evidence' => UploadedFile::fake()->image('versus.png'),
    ], $overrides);
}

function eventStandingRows(array $c): array
{
    return app(EventTeamStandingsService::class)->standings($c['meet']->id, $c['event'])['rows'];
}

beforeEach(function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
});

test('versus measurements preserve decimals without mandatory entries rosters matches or schedules', function (string $measurement, string $winner, string $loser) {
    $c = directResultContext();
    $this->actingAs($c['ict'])->post('/results/direct', versusPayload($c, ['measurement_type' => $measurement, 'winner_value' => $winner, 'loser_value' => $loser]))->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    expect($result->measurement_type)->toBe($measurement)->and($result->status)->toBe(ResultStatus::Submitted)
        ->and($result->placements()->count())->toBe(2)->and($result->placements()->where('rank', 1)->sole()->result_value)->toBe(number_format((float) $winner, 6, '.', ''))
        ->and($result->match_id)->toBeNull()->and($result->event_schedule_id)->toBeNull()->and(Entry::count())->toBe(0)
        ->and(EventMatch::count())->toBe(0)->and(eventStandingRows($c))->toBe([])->and($result->medalAwards()->count())->toBe(0);
    $this->actingAs($c['secretariat'])->post(route('results.event-secretariat.validate', $result))->assertSessionDoesntHaveErrors();
    expect(eventStandingRows($c))->toBe([])->and($result->fresh()->status)->toBe(ResultStatus::Validated);
    $this->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $this->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)->and(eventStandingRows($c)[0]['wins'])->toBe(1)
        ->and(eventStandingRows($c)[0]['played'])->toBe(1)->and(eventStandingRows($c)[1]['losses'])->toBe(1)
        ->and($result->medalAwards()->count())->toBe(0)
        ->and(collect(app(MedalTallyService::class)->standings($c['meet']->id)['districts'])->sum('total'))->toBe(0)
        ->and(AuditLog::where('action', 'result.made_official')->count())->toBe(1);
    auth()->logout();
    $this->get(route('public.sport-event', ['event' => $c['event']->id, 'meet_id' => $c['meet']->id]))->assertInertia(fn ($page) => $page
        ->has('versusResults', 1)->has('results', 0)->has('standings', 0)->where('versusResults.0.measurement_type', $measurement)
        ->where('versusResults.0.placements.0.mark', $winner)->where('teamStandings.rows.0.wins', 1));
    $this->get(route('public.result-document', [$result, $result->attachments()->sole()]))->assertOk();
})->with([
    ['score', '85', '74'], ['points', '3.5', '2.5'], ['time', '48.21', '49.05'], ['distance', '6.42', '6.11'],
]);

test('versus rejects identical delegates invalid measurements values and unauthorized ICT', function () {
    $c = directResultContext();
    $this->actingAs($c['ict'])->get('/results')->assertInertia(fn ($p) => $p->has('delegationOptions', 3));
    $this->post('/results/direct', versusPayload($c, ['loser_delegation_id' => $c['delegations'][0]->id]))->assertSessionHasErrors('loser_delegation_id');
    $this->post('/results/direct', versusPayload($c, ['measurement_type' => 'medals']))->assertSessionHasErrors('measurement_type');
    $this->post('/results/direct', versusPayload($c, ['loser_delegation_id' => null]))->assertSessionHasErrors('loser_delegation_id');
    $this->post('/results/direct', versusPayload($c, ['winner_value' => '48.21 seconds']))->assertSessionHasErrors('winner_value');
    $this->actingAs(User::factory()->create(['role' => UserRole::TournamentICT]))->post('/results/direct', versusPayload($c))->assertForbidden();
    expect(EventResult::count())->toBe(0);
});

test('multiple versus games coexist with medal result and reconcile corrections and cancellation once', function () {
    $c = directResultContext();
    $this->actingAs($c['ict'])->post('/results/direct', directPayload($c))->assertSessionDoesntHaveErrors();
    $medal = EventResult::sole();
    $this->actingAs($c['secretariat'])->post(route('results.official', $medal))->assertSessionDoesntHaveErrors();
    $awards = $medal->medalAwards()->get()->toArray();
    foreach ([1, 2] as $i) {
        $this->actingAs($c['ict'])->post('/results/direct', versusPayload($c, ['loser_delegation_id' => $c['delegations'][$i]->id]))->assertSessionDoesntHaveErrors();
        $game = EventResult::latest('id')->first();
        $this->actingAs($c['secretariat'])->post(route('results.official', $game))->assertSessionDoesntHaveErrors();
    }
    expect(eventStandingRows($c)[0]['played'])->toBe(2)->and(eventStandingRows($c)[0]['wins'])->toBe(2);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $this->actingAs($admin)->post(route('results.reopen', $game), ['reason' => 'Signed sheet corrected'])->assertSessionDoesntHaveErrors();
    expect(eventStandingRows($c)[0]['played'])->toBe(1);
    $this->actingAs($c['ict'])->post(route('results.direct.update', $game), versusPayload($c, [
        'winner_delegation_id' => $c['delegations'][2]->id, 'loser_delegation_id' => $c['delegations'][0]->id,
        'winner_value' => '86', 'loser_value' => '82',
    ]))->assertSessionDoesntHaveErrors();
    $this->actingAs($c['secretariat'])->post(route('results.official', $game))->assertSessionDoesntHaveErrors();
    expect(collect(eventStandingRows($c))->sum('played'))->toBe(4)
        ->and(collect(eventStandingRows($c))->firstWhere('delegation_id', $c['delegations'][0]->id)['losses'])->toBe(1);
    $this->post(route('results.cancel', $game), ['reason' => 'Duplicate sheet cancelled'])->assertSessionDoesntHaveErrors();
    expect(collect(eventStandingRows($c))->sum('played'))->toBe(2)->and($medal->medalAwards()->get()->toArray())->toBe($awards);
});

test('optional individual versus athletes and team roster edits have no effect on standings', function (bool $team) {
    $c = directResultContext();
    $c['event']->update(['is_team_event' => $team]);
    $winner = reportingAthlete($c);
    $loser = reportingAthlete($c, 1);
    $payload = versusPayload($c, $team ? [] : ['winner_attribution' => ['athlete_id' => $winner->id], 'loser_attribution' => ['athlete_id' => $loser->id]]);
    $this->actingAs($c['ict'])->post('/results/direct', $payload)->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    $this->actingAs($c['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $before = eventStandingRows($c);
    $placement = $result->placements()->where('rank', 1)->sole();
    $coach = reportingCoach($c);
    $this->actingAs($coach)->patch(route('results.attribution.update', [$result, $placement]), $team ? ['athlete_ids' => [$winner->id]] : ['athlete_id' => null])->assertSessionDoesntHaveErrors();
    expect(eventStandingRows($c))->toBe($before)->and($result->medalAwards()->count())->toBe(0);
    $this->get(route('reports.result-sheet', $result))->assertInertia(fn ($p) => $p->where('result.result_type', 'versus')->where('result.measurement_type', 'score'));
})->with([true, false]);
