<?php

namespace App\Events;

use App\Models\ScoringSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast on every score-affecting write. Purely additive — the same
 * state this event carries is always available from the polling read
 * endpoint, so a client that never receives this (Reverb not running)
 * still works correctly by polling.
 */
class ScoreUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly ScoringSession $session) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("match.{$this->session->match_id}.scoring"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'score.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['session' => $this->session->toLivePayload()];
    }
}
