<?php

namespace App\Services;

use App\Models\EventMatch;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ScheduleScoreboardService
{
    public static function supports(?string $sport): bool
    {
        return in_array(mb_strtolower(trim((string) $sport)), ['basketball', 'baseball', 'boxing'], true);
    }

    public static function canOperate(User $user): bool
    {
        return $user->isAdmin() || $user->meetSportAssignments()
            ->where('status', 'active')->where('role', 'tournament_ict')
            ->whereHas('meetSport', fn ($scope) => $scope->where('meet_id', Meet::current()->id)
                ->where('active', true)->whereHas('sport', fn ($sport) => $sport->whereIn('name', ['Basketball', 'Baseball', 'Boxing'])))
            ->exists();
    }

    public function sync(EventSchedule $schedule, bool $enabled, string $mode): void
    {
        $match = EventMatch::query()->where('event_schedule_id', $schedule->id)->whereNotNull('scoreboard_mode')->first();
        if ($match && $match->scoringSessions()->exists()
            && ($match->event_id !== $schedule->event_id || $match->scoreboard_mode !== $mode || ! $enabled)) {
            throw ValidationException::withMessages(['live_scoreboard' => 'Keep the event and game type while scoring history exists. Use the scoreboard to reset or start another run.']);
        }
        if (! $enabled && ! $match) {
            return;
        }
        if (! $match) {
            $match = new EventMatch;
            $match->sequence = (EventMatch::query()->where('meet_id', $schedule->meet_id)->where('event_id', $schedule->event_id)->max('sequence') ?? 0) + 1;
        }
        $match->forceFill([
            'meet_id' => $schedule->meet_id, 'event_id' => $schedule->event_id,
            'event_schedule_id' => $schedule->id, 'live_scoring_enabled' => $enabled,
            'scoreboard_mode' => $mode, 'round_label' => self::label($mode), 'awards_medals' => false,
        ])->save();
    }

    public static function label(?string $mode): string
    {
        return match ($mode) { 'finals' => 'Finals Game', 'championship' => 'Championship Game', default => 'Test' };
    }
}
