<?php

use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ScoringSessionStatus;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\ScoringSession;
use App\Models\Sport;
use App\Models\User;
use Database\Seeders\SportIctSeeder;

test('sport ICT accounts are assigned only to their own sport and cannot control another scoreboard', function () {
    $meet = Meet::factory()->create(['name' => 'DdOPAA Meet 2026']);

    $sports = collect(['Basketball', 'Baseball', 'Boxing'])->mapWithKeys(function (string $name) use ($meet): array {
        $sport = Sport::factory()->create(['name' => $name]);
        MeetSport::factory()->create(['meet_id' => $meet->id, 'sport_id' => $sport->id]);

        return [$name => $sport];
    });

    $this->seed(SportIctSeeder::class);

    $basketballIct = User::query()->where('email', 'basketball.ict@ddopaa2026.test')->firstOrFail();

    expect($basketballIct->role)->toBe(UserRole::Organizer)
        ->and(MeetSportAssignment::query()
            ->where('user_id', $basketballIct->id)
            ->where('role', MeetSportAssignmentRole::TournamentICT)
            ->where('status', MeetSportAssignmentStatus::Active)
            ->count())->toBe(1);

    $basketballMatch = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => Event::factory()->create(['sport_id' => $sports['Basketball']->id])->id,
    ]);
    $boxingMatch = EventMatch::factory()->create([
        'meet_id' => $meet->id,
        'event_id' => Event::factory()->create(['sport_id' => $sports['Boxing']->id])->id,
    ]);

    $this->actingAs($basketballIct)
        ->get("/matches/{$basketballMatch->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canManage', true));

    $this->actingAs($basketballIct)
        ->post("/matches/{$basketballMatch->id}/scoring-sessions", [
            'side_a_label' => 'Home',
            'side_b_label' => 'Away',
        ])->assertRedirect()->assertSessionHasNoErrors();

    $session = ScoringSession::query()->where('match_id', $basketballMatch->id)->firstOrFail();

    $this->actingAs($basketballIct)
        ->patch("/scoring-sessions/{$session->id}/pause")
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($session->refresh()->status)->toBe(ScoringSessionStatus::Paused);

    $this->actingAs($basketballIct)
        ->patch("/scoring-sessions/{$session->id}/resume")
        ->assertRedirect()->assertSessionHasNoErrors();
    expect($session->refresh()->status)->toBe(ScoringSessionStatus::InProgress);

    $this->actingAs($basketballIct)
        ->get("/matches/{$boxingMatch->id}/scoreboard")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canManage', false));

    $this->actingAs($basketballIct)
        ->post("/matches/{$boxingMatch->id}/scoring-sessions")
        ->assertForbidden();
});
