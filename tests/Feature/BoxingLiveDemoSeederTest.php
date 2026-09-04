<?php

use App\Enums\ScoringSessionStatus;
use App\Models\EventMatch;
use App\Models\Meet;
use App\Models\ScoringSession;
use Database\Seeders\BoxingLiveDemoSeeder;

test('boxing live-score demo is isolated, playable, and idempotent', function () {
    $this->seed(BoxingLiveDemoSeeder::class);
    $this->seed(BoxingLiveDemoSeeder::class);

    $meet = Meet::query()->where('name', 'Boxing Scoreboard Demo')->sole();
    $match = EventMatch::query()
        ->where('meet_id', $meet->id)
        ->where('round_label', 'Demo Bout')
        ->with('event.sport')
        ->sole();
    $session = ScoringSession::query()->where('match_id', $match->id)->sole();

    expect($meet->is_published)->toBeFalse()
        ->and($match->event->sport->name)->toBe('Boxing')
        ->and($match->entries()->count())->toBe(2)
        ->and($session->status)->toBe(ScoringSessionStatus::Paused)
        ->and($session->score_a)->toBe(10)
        ->and($session->score_b)->toBe(9)
        ->and($session->sport_state['rounds'])->toHaveCount(1)
        ->and($session->events()->count())->toBe(3);
});
