<?php

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MedalAward;
use App\Models\Meet;
use App\Models\ResultPlacement;
use App\Models\Sport;
use App\Models\User;
use App\Services\SportsMedalAwardsReport;
use App\Services\TeamReport;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

function teamReportMedal(Meet $meet, Delegation $team, ?Event $event = null, string $medal = 'gold', int $count = 1, ResultStatus $status = ResultStatus::Official): MedalAward
{
    $event ??= Event::factory()->create();
    $result = EventResult::query()->where('meet_id', $meet->id)->where('event_id', $event->id)->first()
        ?? EventResult::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id, 'result_source' => 'direct', 'status' => $status]);
    $rank = ['gold' => 1, 'silver' => 2, 'bronze' => 3][$medal];
    $placement = ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => null, 'delegation_id' => $team->id,
        'rank' => $rank, 'medal_type' => $medal, 'tally_quantity' => $count]);

    return MedalAward::create(['event_result_id' => $result->id, 'result_placement_id' => $placement->id, 'delegation_id' => $team->id,
        'rank' => $rank, 'medal_type' => $medal, 'physical_quantity' => $count, 'tally_quantity' => $count,
        'result_version' => $result->fresh()->version, 'snapshotted_at' => now()]);
}

test('Admin opens team report and selects a delegation from the selected meet', function () {
    $meet = Meet::factory()->active()->create(['venue' => null]);
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    teamReportMedal($meet, $team, count: 2);
    $this->actingAs(User::factory()->admin()->create())->get('/reports/team')->assertOk()
        ->assertInertia(fn (Assert $p) => $p->component('reports/team-report')->where('team', null)->has('teamOptions', 1));
    $this->get('/reports/team?meet_id='.$meet->id.'&delegation_id='.$team->id)->assertOk()
        ->assertInertia(fn (Assert $p) => $p->where('team.id', $team->id)->where('summary.gold', 2)->where('summary.total', 2)->where('meet.venue', null));
    $foreign = Delegation::factory()->create();
    $this->get('/reports/team?meet_id='.$meet->id.'&delegation_id='.$foreign->id)->assertNotFound();
    $this->get('/reports/team?delegation_id=999999')->assertNotFound();
});

test('non Admin including ICT cannot open team report', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))->get('/reports/team')->assertForbidden();
})->with(['tournament_ict', 'organizer', 'coach', 'viewer']);

test('guest cannot open team report', function () {
    $this->get('/reports/team')->assertRedirect('/login');
});

test('only winning events and sports remain and repeated quantities match canonical report', function () {
    $meet = Meet::factory()->active()->create();
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $other = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $sport = Sport::factory()->create();
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    foreach (['gold', 'silver', 'bronze'] as $medal) {
        teamReportMedal($meet, $team, $event, $medal, 2);
        teamReportMedal($meet, $team, $event, $medal, 3);
    }
    teamReportMedal($meet, $other, $event, count: 50);
    teamReportMedal($meet, $other, Event::factory()->create(['sport_id' => $sport->id]));
    teamReportMedal($meet, $other);
    teamReportMedal($meet, $team, count: 4);
    $data = app(TeamReport::class)->build($meet, $team->id);
    expect($data['sports'])->toHaveCount(2)
        ->and(collect($data['sports'])->sum(fn ($s) => count($s['events'])))->toBe(2)
        ->and($data['summary'])->toBe(['gold' => 9, 'silver' => 5, 'bronze' => 5, 'total' => 19]);
    $canonical = app(SportsMedalAwardsReport::class)->build($meet, null, $team->id);
    expect($data['sports'])->toBe($canonical);
});

test('team with no medals has zero summary and no empty sports', function () {
    $meet = Meet::factory()->active()->create();
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    teamReportMedal($meet, $team, count: 0);
    $data = app(TeamReport::class)->build($meet, $team->id);
    expect($data['sports'])->toBe([])->and($data['summary'])->toBe(['gold' => 0, 'silver' => 0, 'bronze' => 0, 'total' => 0]);
});

test('only accepted current result versions contribute', function () {
    $meet = Meet::factory()->active()->create();
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    foreach (ResultStatus::cases() as $status) {
        teamReportMedal($meet, $team, status: $status);
    }
    $stale = teamReportMedal($meet, $team);
    $stale->update(['result_version' => 999]);
    expect(app(TeamReport::class)->build($meet, $team->id)['summary']['total'])->toBe(1);
});

test('individual athlete and saved team attribution display while missing staff remains blank', function () {
    $meet = Meet::factory()->active()->create();
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $award = teamReportMedal($meet, $team);
    $athlete = Athlete::factory()->create(['delegation_id' => $team->id]);
    $award->placement->update(['athlete_id' => $athlete->id, 'mark' => '10.52']);
    $data = app(TeamReport::class)->build($meet, $team->id);
    expect($data['sports'][0]['events'][0]['awards'][0]['athletes'])->toBe([$athlete->fullName()])
        ->and($data['sports'][0]['events'][0]['awards'][0]['mark'])->toBe('10.52')
        ->and($data['sports'][0]['signatories'][0]['name'])->toBeNull();
    $award->placement->update(['athlete_id' => null]);
    $award->result->event->update(['is_team_event' => true]);
    $award->placement->reportingAthletes()->attach($athlete);
    expect(app(TeamReport::class)->build($meet, $team->id)['sports'][0]['events'][0]['awards'][0]['athletes'])->toBe([$athlete->fullName()]);
});

test('missing athlete event and sport never discard a known team award', function () {
    $meet = Meet::factory()->active()->create();
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $award = teamReportMedal($meet, $team, count: 2);
    DB::statement('PRAGMA defer_foreign_keys = ON');
    try {
        DB::table('result_placements')->where('id', $award->result_placement_id)->update(['athlete_id' => 999999]);
        DB::table('events')->where('id', $award->result->event_id)->update(['sport_id' => 999999]);
        $data = app(TeamReport::class)->build($meet, $team->id);
        expect($data['summary']['total'])->toBe(2)->and($data['sports'][0]['name'])->toBeNull()
            ->and($data['sports'][0]['events'][0]['awards'][0]['athletes'])->toBe([]);
        DB::table('event_results')->where('id', $award->event_result_id)->update(['event_id' => 999999]);
        $data = app(TeamReport::class)->build($meet, $team->id);
        expect($data['summary']['total'])->toBe(2)->and($data['sports'][0]['events'][0]['name'])->toBeNull();
    } finally {
        DB::statement('PRAGMA defer_foreign_keys = OFF');
    }
});

test('query count stays fixed as team award rows grow', function () {
    $meet = Meet::factory()->active()->create();
    $team = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $award = teamReportMedal($meet, $team);
    DB::enableQueryLog();
    DB::flushQueryLog();
    app(TeamReport::class)->build($meet, $team->id);
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();
    for ($i = 0; $i < 10; $i++) {
        teamReportMedal($meet, $team, $award->result->event);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    app(TeamReport::class)->build($meet, $team->id);
    expect(count(DB::getQueryLog()))->toBe($count);
    DB::disableQueryLog();
});
