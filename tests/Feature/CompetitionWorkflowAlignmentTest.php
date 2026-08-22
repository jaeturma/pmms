<?php

use App\Enums\MatchStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\ScoringSession;
use App\Models\User;

function alignedCompetition(): array
{
    $meet = Meet::factory()->active()->create();
    $event = Event::factory()->create();
    $meet->events()->attach($event);
    $schedule = EventSchedule::factory()->create(['meet_id' => $meet->id, 'event_id' => $event->id]);
    $match = EventMatch::factory()->completed()->create([
        'meet_id' => $meet->id,
        'event_id' => $event->id,
        'event_schedule_id' => $schedule->id,
        'awards_medals' => true,
    ]);
    $delegation = Delegation::factory()->approved()->create(['meet_id' => $meet->id]);
    $entries = collect(range(1, 2))->map(function () use ($delegation, $event) {
        $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);
        return Entry::factory()->confirmed()->create(['athlete_id' => $athlete->id, 'delegation_id' => $delegation->id, 'event_id' => $event->id]);
    });
    $match->entries()->attach($entries->pluck('id'));
    return compact('meet', 'event', 'schedule', 'match', 'entries');
}

test('manual results inherit their completed scheduled competition context', function () {
    $this->withoutExceptionHandling();
    $fixture = alignedCompetition();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->post('/results', [
        'match_id' => $fixture['match']->id,
        'meet_id' => 999999,
        'event_id' => 999999,
        'placements' => [
            ['entry_id' => $fixture['entries'][0]->id, 'rank' => 1],
            ['entry_id' => $fixture['entries'][1]->id, 'rank' => 2],
        ],
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('event_results', [
        'match_id' => $fixture['match']->id,
        'meet_id' => $fixture['meet']->id,
        'event_id' => $fixture['event']->id,
        'event_schedule_id' => $fixture['schedule']->id,
        'result_source' => 'manual',
    ]);
    expect(AuditLog::query()->where('action', 'result.manually_entered')->exists())->toBeTrue();
});

test('results reject unscheduled incomplete or foreign participants', function () {
    $fixture = alignedCompetition();
    $fixture['match']->forceFill(['status' => MatchStatus::Scheduled])->save();
    $user = User::factory()->admin()->create();

    $this->actingAs($user)->post('/results', [
        'match_id' => $fixture['match']->id,
        'placements' => [['entry_id' => $fixture['entries'][0]->id, 'rank' => 1]],
    ])->assertSessionHasErrors('match_id');

    $fixture['match']->forceFill(['status' => MatchStatus::Completed])->save();
    $foreign = Entry::factory()->confirmed()->create();
    $this->actingAs($user)->post('/results', [
        'match_id' => $fixture['match']->id,
        'placements' => [['entry_id' => $foreign->id, 'rank' => 1]],
    ])->assertSessionHasErrors('placements');
});

test('only one result can be entered for a scheduled competition', function () {
    $fixture = alignedCompetition();
    $payload = ['match_id' => $fixture['match']->id, 'placements' => [['entry_id' => $fixture['entries'][0]->id, 'rank' => 1]]];
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->post('/results', $payload)->assertSessionHasNoErrors();
    $this->actingAs($user)->post('/results', $payload)->assertSessionHasErrors('match_id');
});

test('ending enabled live scoring creates an encoded draft from the final score', function () {
    $fixture = alignedCompetition();
    $fixture['match']->forceFill(['status' => MatchStatus::Scheduled, 'live_scoring_enabled' => true])->save();
    $user = User::factory()->admin()->create();
    $session = ScoringSession::factory()->create([
        'match_id' => $fixture['match']->id,
        'score_a' => 87,
        'score_b' => 81,
        'started_by' => $user->id,
    ]);

    $this->actingAs($user)->patch("/scoring-sessions/{$session->id}/end")->assertSessionHasNoErrors();

    $result = $fixture['match']->fresh()->result;
    expect($result)->not->toBeNull()
        ->and($result->result_source)->toBe('live_score')
        ->and($result->placements()->where('rank', 1)->value('entry_id'))->toBe($fixture['entries'][0]->id)
        ->and($result->placements()->where('rank', 1)->value('mark'))->toBe('87-81');
    expect(AuditLog::query()->where('action', 'result.created_from_live_score')->exists())->toBeTrue();
});

test('a match result requires sport-level confirmation before its result form', function () {
    $fixture = alignedCompetition();
    $user = User::factory()->admin()->create();
    $this->actingAs($user)->post('/results', [
        'match_id' => $fixture['match']->id,
        'placements' => [['entry_id' => $fixture['entries'][0]->id, 'rank' => 1]],
    ])->assertSessionHasNoErrors();

    $result = $fixture['match']->fresh()->result;
    $this->actingAs($user)->get("/results/{$result->id}/form")->assertStatus(422);

    $this->actingAs($user)->post("/results/{$result->id}/tm-confirmation")
        ->assertSessionHasNoErrors();

    expect($result->fresh()->tm_confirmed_by)->toBe($user->id)
        ->and($result->fresh()->tm_confirmed_at)->not->toBeNull();
    $this->actingAs($user)->get("/results/{$result->id}/form")->assertOk();
});
