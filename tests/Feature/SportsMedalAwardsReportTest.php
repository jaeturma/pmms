<?php

use App\Enums\ResultStatus;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\EventResult;
use App\Models\MedalAward;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\ResultPlacement;
use App\Models\Sport;
use App\Models\User;
use App\Services\SportsMedalAwardsReport;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->withoutVite());

function medalReportContext(): array
{
    $meet = Meet::factory()->active()->create(['venue' => null]);
    $sports = collect(['Athletics', 'Kickboxing'])->map(fn ($name) => Sport::factory()->create(['name' => $name]));
    $scopes = $sports->map(fn ($sport) => MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]));
    $ict = User::factory()->create(['role' => 'tournament_ict']);
    MeetSportAssignment::factory()->create(['meet_sport_id' => $scopes[0]->id, 'user_id' => $ict->id, 'role' => 'tournament_ict', 'status' => 'active']);

    return compact('meet', 'sports', 'scopes', 'ict');
}

function medalReportAward(array $context, int $sportIndex = 0, array $resultFields = []): MedalAward
{
    $event = Event::factory()->create(['sport_id' => $context['sports'][$sportIndex]->id]);
    $result = EventResult::factory()->validated()->create(['meet_id' => $context['meet']->id, 'event_id' => $event->id, 'result_source' => 'direct', ...$resultFields]);
    $placement = ResultPlacement::factory()->create(['event_result_id' => $result->id, 'entry_id' => null, 'rank' => 1, 'medal_type' => 'gold', 'tally_quantity' => 3]);

    return MedalAward::create(['event_result_id' => $result->id, 'result_placement_id' => $placement->id, 'delegation_id' => Delegation::factory()->approved()->create(['meet_id' => $context['meet']->id])->id, 'rank' => 1, 'medal_type' => 'gold', 'physical_quantity' => 5, 'tally_quantity' => 3, 'result_version' => $result->fresh()->version, 'snapshotted_at' => now()]);
}

test('ICT defaults to its one sport and cannot bypass sport or meet scope', function () {
    $c = medalReportContext();
    medalReportAward($c);
    $this->actingAs($c['ict'])->get('/reports/sports-medal-awards')->assertOk()->assertInertia(fn (Assert $p) => $p
        ->component('reports/sports-medal-awards')->where('sportId', $c['sports'][0]->id)->has('sportOptions', 1)->where('canSelectAll', false));
    $this->get('/reports/sports-medal-awards?sport_id='.$c['sports'][1]->id)->assertForbidden();
    $this->get('/reports/sports-medal-awards?sport_id=')->assertForbidden();
    $this->get('/reports/sports-medal-awards?sport_id=0')->assertSessionHasErrors('sport_id');
    $other = Meet::factory()->create();
    $this->get('/reports/sports-medal-awards?meet_id='.$other->id)->assertForbidden();
});

test('ICT can choose among multiple assigned sports while Admin can choose all', function () {
    $c = medalReportContext();
    medalReportAward($c);
    medalReportAward($c, 1);
    MeetSportAssignment::factory()->create(['meet_sport_id' => $c['scopes'][1]->id, 'user_id' => $c['ict']->id, 'role' => 'tournament_ict', 'status' => 'active']);
    $this->actingAs($c['ict'])->get('/reports/sports-medal-awards?sport_id='.$c['sports'][1]->id)->assertOk()
        ->assertInertia(fn (Assert $p) => $p->has('sportOptions', 2)->has('sports', 1)->where('sports.0.name', 'Kickboxing'));
    $this->actingAs(User::factory()->admin()->create())->get('/reports/sports-medal-awards')->assertOk()
        ->assertInertia(fn (Assert $p) => $p->has('sports', 2)->where('sportId', null)->where('canSelectAll', true)->where('meet.venue', null));
    $this->get('/reports/sports-medal-awards?sport_id='.$c['sports'][0]->id)->assertOk()
        ->assertInertia(fn (Assert $p) => $p->has('sports', 1));
});

test('awards preserve repeated team rows, attribution, quantities and partial signatories', function () {
    $c = medalReportContext();
    $award = medalReportAward($c);
    $result = $award->result;
    $result->event->update(['is_team_event' => true]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $c['meet']->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $coach = User::factory()->create(['name' => 'Configured Coach']);
    $award->update(['delegation_id' => $delegation->id]);
    $award->placement->reportingAthletes()->attach($athlete);
    $award->placement->reportingCoaches()->attach($coach, ['role' => 'coach']);
    $copy = $award->placement->replicate();
    $copy->save();
    $second = $award->replicate();
    $second->result_placement_id = $copy->id;
    $second->save();
    medalReportAward($c);
    MeetSportAssignment::factory()->create(['meet_sport_id' => $c['scopes'][0]->id, 'role' => 'tournament_secretary', 'status' => 'active', 'user_id' => $coach->id]);
    $data = app(SportsMedalAwardsReport::class)->build($c['meet'], null);
    $event = collect($data[0]['events'])->firstWhere('id', $result->id);
    expect($data[0]['events'])->toHaveCount(2)
        ->and($event['awards'])->toHaveCount(2)
        ->and($event['awards'][0]['athletes'])->toBe([$athlete->fullName()])
        ->and($event['awards'][0]['coaches'][0]['name'])->toBe('Configured Coach')
        ->and(array_column($event['awards'], 'tally_count'))->toBe([3, 3])
        ->and(array_column($event['awards'], 'physical_count'))->toBe([5, 5])
        ->and($event['awards'][0]['team'])->toBe($event['awards'][1]['team'])
        ->and($data[0]['signatories'][0]['name'])->toBe('Configured Coach')
        ->and($data[0]['signatories'][1]['name'])->toBeNull();
});

test('only current official snapshots report and missing relationships remain harmless', function () {
    $c = medalReportContext();
    foreach (ResultStatus::cases() as $status) {
        medalReportAward($c, 0, ['status' => $status]);
    }
    $stale = medalReportAward($c);
    $stale->update(['result_version' => 99]);
    $data = app(SportsMedalAwardsReport::class)->build($c['meet'], null);
    $awards = collect($data[0]['events'])->pluck('awards')->flatten(1);
    expect($awards)->toHaveCount(1)->and($awards[0]['athletes'])->toBe([])->and($awards[0]['coaches'])->toBe([])->and($awards[0]['team'])->toBeString()
        ->and($data[0]['signatories'][0]['name'])->toBeNull();
});

test('individual attribution is used and report queries do not grow per award', function () {
    $c = medalReportContext();
    $award = medalReportAward($c);
    $athlete = Athlete::factory()->create();
    $award->placement->update(['athlete_id' => $athlete->id]);
    DB::enableQueryLog();
    DB::flushQueryLog();
    $data = app(SportsMedalAwardsReport::class)->build($c['meet'], null);
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();
    expect($data[0]['events'][0]['awards'][0]['athletes'])->toBe([$athlete->fullName()]);
    for ($i = 0; $i < 12; $i++) {
        $placement = $award->placement->replicate();
        $placement->save();
        $copy = $award->replicate();
        $copy->result_placement_id = $placement->id;
        $copy->save();
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    app(SportsMedalAwardsReport::class)->build($c['meet'], null);
    expect(count(DB::getQueryLog()))->toBe($count);
    DB::disableQueryLog();
});

test('guests and users without ICT assignments cannot open the report', function () {
    $this->get('/reports/sports-medal-awards')->assertRedirect('/login');
    $this->actingAs(User::factory()->create())->get('/reports/sports-medal-awards')->assertForbidden();
});

test('orphaned award relationships preserve available medal data', function () {
    $c = medalReportContext();
    $award = medalReportAward($c);
    DB::statement('PRAGMA defer_foreign_keys = ON');
    try {
        DB::table('medal_awards')->where('id', $award->id)->update(['delegation_id' => 999999, 'result_placement_id' => 999999]);
        DB::table('events')->where('id', $award->result->event_id)->update(['sport_id' => 999999]);
        $data = app(SportsMedalAwardsReport::class)->build($c['meet'], null);
        expect($data[0]['name'])->toBeNull()
            ->and($data[0]['events'][0]['awards'][0]['team'])->toBeNull()
            ->and($data[0]['events'][0]['awards'][0]['medal'])->toBe('gold');
    } finally {
        DB::statement('PRAGMA defer_foreign_keys = OFF');
    }
});


test('team entry delegation and saved roster resolve without reconstructing players', function () {
    $c = medalReportContext();
    $award = medalReportAward($c);
    $result = $award->result;
    $result->event->update(['is_team_event' => true]);
    $delegation = $award->delegation;
    $team = \App\Models\TeamEntry::create(['delegation_id' => $delegation->id, 'event_id' => $result->event_id, 'status' => 'confirmed']);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
    $award->placement->update(['team_entry_id' => $team->id]);
    $award->placement->reportingAthletes()->attach($athlete);
    DB::statement('PRAGMA defer_foreign_keys = ON');
    try {
        DB::table('medal_awards')->where('id', $award->id)->update(['delegation_id' => 999999]);
        $data = app(SportsMedalAwardsReport::class)->build($c['meet'], null);
        expect($data[0]['events'][0]['awards'][0]['team'])->toBe($delegation->registrantName())
            ->and($data[0]['events'][0]['awards'][0]['athletes'])->toBe([$athlete->fullName()]);
        DB::table('event_results')->where('id', $result->id)->update(['event_id' => 999999]);
        $data = app(SportsMedalAwardsReport::class)->build($c['meet'], null);
        expect($data[0]['events'][0]['name'])->toBeNull();
    } finally {
        DB::statement('PRAGMA defer_foreign_keys = OFF');
    }
});
