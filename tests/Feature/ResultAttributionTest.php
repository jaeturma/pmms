<?php

use App\Enums\EntryStatus;
use App\Enums\ResultStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\CoachAssignmentRequest;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use App\Models\SportRosterMember;
use App\Models\TeamEntry;
use App\Models\User;
use App\Services\MedalTallyService;
use App\Services\PublicEventResults;
use Illuminate\Support\Facades\Storage;

function reportingAthlete(array $context, int $delegationIndex = 0): Athlete
{
    $athlete = Athlete::factory()->create(['delegation_id' => $context['delegations'][$delegationIndex]->id]);
    SportRosterMember::create([
        'meet_sport_id' => MeetSport::where('meet_id', $context['meet']->id)->where('sport_id', $context['event']->sport_id)->sole()->id,
        'delegation_id' => $athlete->delegation_id, 'athlete_id' => $athlete->id,
        'level' => $context['event']->age_division, 'gender' => $context['event']->gender,
    ]);

    return $athlete;
}

function reportingCoach(array $context, int $delegationIndex = 0): User
{
    $coach = User::factory()->create(['role' => UserRole::Coach]);
    CoachAssignmentRequest::create([
        'user_id' => $coach->id, 'meet_sport_id' => MeetSport::where('meet_id', $context['meet']->id)->where('sport_id', $context['event']->sport_id)->sole()->id,
        'event_id' => $context['event']->id, 'delegation_id' => $context['delegations'][$delegationIndex]->id,
        'scope_type' => 'event', 'status' => 'approved',
    ]);

    return $coach;
}

beforeEach(function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
});

test('individual attribution filters sport meet delegation and excludes deleted athletes without requiring entries', function () {
    $c = directResultContext();
    $c['event']->update(['is_team_event' => false]);
    $valid = reportingAthlete($c);
    reportingAthlete($c, 1);
    reportingAthlete($c)->delete();
    Athlete::factory()->create(['delegation_id' => $c['delegations'][0]->id]);
    $url = '/results/attribution-options?event_id='.$c['event']->id.'&delegation_id='.$c['delegations'][0]->id;
    $this->actingAs($c['ict'])->getJson($url)->assertOk()->assertJsonCount(1, 'athletes')->assertJsonPath('athletes.0.id', $valid->id);
    $this->get('/results')->assertInertia(fn ($p) => $p->where('eventOptionsByMeet.0.is_team_event', false));
    $wrong = reportingAthlete($c, 1);
    $this->post('/results/direct', [...directPayload($c), 'gold_attribution' => ['athlete_id' => $wrong->id]])->assertSessionHasErrors('attribution');
    expect(EventResult::count())->toBe(0);
});

test('same delegation wins all medals with individual athletes and attribution does not affect acceptance or tally', function () {
    $c = directResultContext();
    $c['event']->update(['is_team_event' => false]);
    $athletes = collect(range(1, 3))->map(fn () => reportingAthlete($c));
    $payload = directPayload($c);
    foreach (['gold', 'silver', 'bronze'] as $i => $medal) {
        $payload[$medal.'_delegation_id'] = $c['delegations'][0]->id;
        $payload[$medal.'_attribution'] = ['athlete_id' => $athletes[$i]->id];
    }
    $this->actingAs($c['ict'])->post('/results/direct', $payload)->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    expect($result->placements->pluck('athlete_id')->all())->toBe($athletes->pluck('id')->all());
    $this->actingAs($c['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official)->and($result->medalAwards()->sum('tally_quantity'))->toBe(3);
    expect(app(PublicEventResults::class)->row($result->fresh())['placements'][0]['athlete'])->toBe($athletes[0]->fullName());
});

test('accepted individual result without athlete can be completed by own coach with audit and immutable awards', function () {
    $c = directResultContext();
    $c['event']->update(['is_team_event' => false]);
    $this->actingAs($c['ict'])->post('/results/direct', directPayload($c, ['silver_delegation_id' => $c['delegations'][1]->id]))->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    $this->actingAs($c['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $awards = $result->medalAwards()->get()->toArray();
    $before = $result->fresh()->toArray();
    $coach = reportingCoach($c);
    $athlete = reportingAthlete($c);
    $placement = $result->placements()->where('rank', 1)->sole();
    $url = route('results.attribution.update', [$result, $placement]);
    $this->actingAs($coach)->get('/results')->assertInertia(fn ($p) => $p->has('results.data', 1)->where('results.data.0.placements.0.can_attribute', true));
    $this->patch($url, ['athlete_id' => $athlete->id])->assertSessionDoesntHaveErrors();
    expect($placement->fresh()->athlete_id)->toBe($athlete->id)
        ->and($result->fresh()->toArray())->toBe($before)
        ->and($result->medalAwards()->get()->toArray())->toBe($awards)
        ->and(AuditLog::where('action', 'result.attribution_updated')->count())->toBe(1);
    $this->patch($url, ['athlete_id' => $athlete->id, 'rank' => 2])->assertSessionHasErrors('rank');
    $other = $result->placements()->where('rank', 2)->sole();
    $this->patch(route('results.attribution.update', [$result, $other]), ['athlete_id' => $athlete->id])->assertForbidden();
    $this->post(route('results.event-secretariat.validate', $result))->assertForbidden();
});

test('team reporting permits incomplete rosters then fifteen players and coaches without changing accepted gold', function () {
    $c = directResultContext();
    $this->actingAs($c['ict'])->get('/results')->assertInertia(fn ($p) => $p->where('eventOptionsByMeet.0.is_team_event', true));
    $this->post('/results/direct', [...directPayload($c), 'silver_delegation_id' => null, 'bronze_delegation_id' => null])->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    $this->actingAs($c['secretariat'])->post(route('results.official', $result))->assertSessionDoesntHaveErrors();
    $placement = $result->placements()->sole();
    $before = $result->medalAwards()->get()->toArray();
    $coach = reportingCoach($c);
    $assistant = reportingCoach($c);
    $ids = collect(range(1, 15))->map(fn () => reportingAthlete($c)->id)->all();
    $url = route('results.attribution.update', [$result, $placement]);
    $this->actingAs($coach)->patch($url, ['athlete_ids' => $ids, 'coaches' => [
        ['user_id' => $coach->id, 'role' => 'primary'], ['user_id' => $assistant->id, 'role' => 'assistant'],
    ]])->assertSessionDoesntHaveErrors();
    expect($placement->reportingAthletes()->count())->toBe(15)
        ->and($result->medalAwards()->get()->toArray())->toBe($before)
        ->and(collect(app(MedalTallyService::class)->standings($c['meet']->id)['districts'])->sum('gold'))->toBe(1);
    $this->get(route('reports.result-sheet', $result))->assertInertia(fn ($p) => $p->has('placements.0.attribution.players', 15)->where('placements.0.attribution.coaches.0.name', $coach->name));
    $wrong = reportingAthlete($c, 1);
    $this->patch($url, ['athlete_ids' => [$wrong->id]])->assertSessionHasErrors('attribution');
    $this->actingAs($c['ict'])->patch($url, ['athlete_ids' => array_slice($ids, 0, 2)])->assertSessionDoesntHaveErrors();
    expect($placement->reportingAthletes()->count())->toBe(2)->and($placement->reportingCoaches()->count())->toBe(2)->and($result->medalAwards()->get()->toArray())->toBe($before);
    $this->actingAs(User::factory()->create(['role' => UserRole::TournamentICT]))->patch($url, ['athlete_ids' => []])->assertForbidden();
    $this->actingAs($c['secretariat'])->patch($url, ['athlete_ids' => []])->assertSessionDoesntHaveErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Official);
});

test('team entry reporting import snapshots athlete ids and rejects individual selector', function () {
    $c = directResultContext();
    $athlete = reportingAthlete($c);
    $team = TeamEntry::create(['event_id' => $c['event']->id, 'delegation_id' => $c['delegations'][0]->id, 'status' => EntryStatus::Submitted]);
    $entry = Entry::factory()->create(['event_id' => $c['event']->id, 'delegation_id' => $c['delegations'][0]->id, 'athlete_id' => $athlete->id]);
    $team->members()->create(['athlete_id' => $athlete->id, 'entry_id' => $entry->id]);
    $this->actingAs($c['ict'])->post('/results/direct', [...directPayload($c), 'gold_attribution' => ['team_entry_id' => $team->id]])->assertSessionDoesntHaveErrors();
    $placement = EventResult::sole()->placements()->where('rank', 1)->sole();
    $team->members()->delete();
    expect($placement->reportingAthletes()->pluck('athletes.id')->all())->toBe([$athlete->id]);
    $this->patch(route('results.attribution.update', [$placement->result, $placement]), ['athlete_id' => $athlete->id])->assertSessionHasErrors('attribution');
});

test('attribution rejects other sports and meets and unauthorized coach assignments', function () {
    $c = directResultContext();
    $c['event']->update(['is_team_event' => false]);
    $athlete = reportingAthlete($c);
    $wrongSport = Sport::factory()->create();
    $wrongScope = MeetSport::factory()->create(['meet_id' => $c['meet']->id, 'sport_id' => $wrongSport->id]);
    $athlete->sportRosterMemberships()->update(['meet_sport_id' => $wrongScope->id]);
    $url = '/results/attribution-options?event_id='.$c['event']->id.'&delegation_id='.$c['delegations'][0]->id;
    $this->actingAs($c['ict'])->getJson($url)->assertJsonCount(0, 'athletes');
    $this->post('/results/direct', [...directPayload($c), 'gold_attribution' => ['athlete_id' => $athlete->id]])->assertSessionHasErrors('attribution');
    $otherMeet = Meet::factory()->create();
    $scope = MeetSport::factory()->create(['meet_id' => $otherMeet->id, 'sport_id' => $c['event']->sport_id]);
    $athlete->sportRosterMemberships()->update(['meet_sport_id' => $scope->id]);
    $this->getJson($url)->assertJsonCount(0, 'athletes');
    $this->post('/results/direct', directPayload($c))->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    $coach = reportingCoach($c, 1);
    $this->actingAs($coach)->getJson($url)->assertForbidden();
    $this->patch(route('results.attribution.update', [$result, $result->placements()->first()]), ['athlete_id' => null])->assertForbidden();
    $this->get('/results')->assertInertia(fn ($p) => $p->has('results.data', 0));
});

test('scoped coach can enrich submitted result while admin can correct and legacy direct edits preserve links', function () {
    $c = directResultContext();
    $c['event']->update(['is_team_event' => false]);
    $athlete = reportingAthlete($c);
    $this->actingAs($c['ict'])->post('/results/direct', [...directPayload($c), 'gold_attribution' => ['athlete_id' => $athlete->id]])->assertSessionDoesntHaveErrors();
    $result = EventResult::sole();
    $coach = reportingCoach($c);
    $this->actingAs($coach)->get('/results')->assertInertia(fn ($p) => $p->has('results.data', 1));
    $this->actingAs($c['ict'])->post(route('results.direct.update', $result), directPayload($c))->assertSessionDoesntHaveErrors();
    $placement = $result->placements()->where('rank', 1)->sole();
    expect($placement->athlete_id)->toBe($athlete->id);
    $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))->patch(route('results.attribution.update', [$result, $placement]), ['athlete_id' => null])->assertSessionDoesntHaveErrors();
    expect($placement->fresh()->athlete_id)->toBeNull()->and($result->fresh()->status)->toBe(ResultStatus::Submitted);
});
