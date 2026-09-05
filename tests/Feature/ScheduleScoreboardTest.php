<?php

use App\Enums\MeetStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\Sport;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia as Assert;

test('schedule scoreboards can run reset and end without altering results', function (string $sportName) {
    $this->withoutVite();
    $meet = Meet::current();
    $meet->forceFill(['status' => MeetStatus::Active, 'is_published' => true])->save();
    $sport = Sport::factory()->create(['name' => $sportName]);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    $meet->events()->attach($event);
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id, 'active' => true]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create(['user_id' => $ict->id, 'meet_sport_id' => $meetSport->id, 'role' => 'tournament_ict', 'status' => 'active']);
    $this->actingAs($ict)->post('/schedule', [
        'event_id' => $event->id, 'venue_id' => Venue::factory()->create()->id,
        'scheduled_date' => '2026-09-06', 'starts_at' => '08:00', 'ends_at' => '09:00',
        'live_scoreboard' => true, 'scoreboard_mode' => 'test',
    ])->assertRedirect()->assertSessionDoesntHaveErrors();
    $match = EventMatch::query()->where('event_id', $event->id)->firstOrFail();
    expect($match->event_schedule_id)->not->toBeNull();
    $this->get(route('public.scoreboard', [$meet->id, $match->id]))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('match.scoreboard_mode', 'test')->where('session', null));
    $this->get('/scoreboards')->assertOk()->assertInertia(fn (Assert $page) => $page->component('scoring/index')->has('matches', 1)->where('matches.0.mode', 'Test'));
    $this->post("/matches/{$match->id}/scoring-sessions", ['side_a_label' => 'Red', 'side_b_label' => 'Blue'])
        ->assertRedirect()->assertSessionDoesntHaveErrors();
    $first = $match->scoringSessions()->latest('id')->firstOrFail();
    $first->forceFill(['score_a' => 7])->save();
    $this->post("/matches/{$match->id}/scoreboard/reset", ['scoreboard_mode' => 'championship'])
        ->assertRedirect()->assertSessionDoesntHaveErrors();
    $next = $match->scoringSessions()->latest('id')->firstOrFail();
    $this->get(route('public.scoreboard.poll', [$meet->id, $match->id]))->assertOk()
        ->assertJsonPath('session.id', $next->id)->assertJsonPath('session.scoreboard_mode', 'championship');
    expect($next->id)->not->toBe($first->id)
        ->and($next->score_a)->toBe(0)
        ->and($first->fresh()->score_a)->toBe(7)
        ->and($first->fresh()->status->value)->toBe('ended')
        ->and($next->toLivePayload()['scoreboard_mode'])->toBe('championship');
    $this->patch("/scoring-sessions/{$next->id}/end")->assertRedirect()->assertSessionDoesntHaveErrors();
    expect(EventResult::query()->count())->toBe(0);
    $this->post("/matches/{$match->id}/scoreboard/reset", ['scoreboard_mode' => 'finals'])->assertRedirect()->assertSessionDoesntHaveErrors();
    expect($match->scoringSessions()->count())->toBe(3);
    $this->actingAs(User::factory()->create(['role' => UserRole::TournamentICT]))
        ->post("/matches/{$match->id}/scoreboard/reset")->assertForbidden();
})->with(['Basketball', 'Baseball', 'Boxing']);

test('unsupported sports cannot enable a schedule scoreboard', function () {
    Meet::current()->forceFill(['status' => MeetStatus::Active])->save();
    $sport = Sport::factory()->create(['name' => 'Swimming']);
    $event = Event::factory()->create(['sport_id' => $sport->id]);
    Meet::current()->events()->attach($event);
    $this->actingAs(User::factory()->admin()->create())->post('/schedule', [
        'event_id' => $event->id, 'venue_id' => Venue::factory()->create()->id,
        'scheduled_date' => '2026-09-06', 'starts_at' => '08:00', 'ends_at' => '09:00', 'live_scoreboard' => true,
    ])->assertSessionHasErrors('live_scoreboard');
});
