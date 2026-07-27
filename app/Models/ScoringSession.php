<?php

namespace App\Models;

use App\Enums\ScoreboardType;
use App\Enums\ScoreEventType;
use App\Enums\ScoringSessionStatus;
use Database\Factories\ScoringSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A provisional, live running score for one match. Never creates or
 * implies an EventResult/ResultPlacement — Phase 3's encode->validate
 * pipeline is the only path to an official result, untouched by this.
 *
 * @property int $id
 * @property int $match_id
 * @property ScoringSessionStatus $status
 * @property string $side_a_label
 * @property string $side_b_label
 * @property int $score_a
 * @property int $score_b
 * @property string|null $period_label
 * @property string|null $status_note
 * @property array<string, mixed>|null $sport_state
 * @property ScoreboardType|null $board_type_override
 * @property int|null $started_by
 * @property int|null $ended_by
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['match_id', 'side_a_label', 'side_b_label'])]
class ScoringSession extends Model
{
    /** @use HasFactory<ScoringSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ScoringSessionStatus::class,
            'sport_state' => 'array',
            'board_type_override' => ScoreboardType::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EventMatch, $this>
     */
    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    /**
     * @return HasMany<ScoreEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ScoreEvent::class);
    }

    public function isActive(): bool
    {
        return $this->status !== ScoringSessionStatus::Ended;
    }

    /**
     * Which scoreboard UI this session uses. Normally inferred from the
     * match's sport (App\Enums\ScoreboardType) and not stored, so a sport
     * rename or catalog edit is reflected immediately — unless the
     * operator explicitly overrode it at session start (`board_type_
     * override`, WP-07-07, generic-only), which always wins.
     */
    public function boardType(): ScoreboardType
    {
        if ($this->board_type_override !== null) {
            return $this->board_type_override;
        }

        $this->loadMissing('match.event.sport');

        return ScoreboardType::forSport($this->match->event->sport->name);
    }

    /**
     * The one shape sent to the frontend, whether by the polling read
     * endpoint or the Reverb broadcast — kept identical so the client
     * never has to reconcile two different payload shapes.
     *
     * @return array<string, mixed>
     */
    public function toLivePayload(): array
    {
        return [
            'id' => $this->id,
            'match_id' => $this->match_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'side_a_label' => $this->side_a_label,
            'side_b_label' => $this->side_b_label,
            'score_a' => $this->score_a,
            'score_b' => $this->score_b,
            'period_label' => $this->period_label,
            'status_note' => $this->status_note,
            'board_type' => $this->boardType()->value,
            'sport_state' => $this->sport_state,
            'playByPlay' => $this->playByPlay(),
        ];
    }

    /**
     * A real, read-only play-by-play feed reconstructed from the
     * append-only `score_events` log (WP-08-10) — every board type gets
     * this for free since it lives on the shared model, not just
     * basketball. Both running scores are reconstructed by replaying
     * every point/correction delta in order (mirroring
     * `ScoringSessionController::score()`'s own `max(0, ...)` floor)
     * since a single event's payload only records the one side it
     * changed. Newest first, capped — this is a feed, not a paginated
     * archive.
     *
     * @return array<int, array{id: int, description: string, score_a: int, score_b: int, created_at: string|null}>
     */
    public function playByPlay(int $limit = 30): array
    {
        $runningA = 0;
        $runningB = 0;
        $rows = [];

        foreach ($this->events()->oldest('id')->get() as $event) {
            $payload = $event->payload ?? [];

            if (in_array($event->type, [ScoreEventType::Point, ScoreEventType::Correction], true)) {
                $delta = (int) ($payload['delta'] ?? 0);

                if (($payload['side'] ?? null) === 'a') {
                    $runningA = max(0, $runningA + $delta);
                } elseif (($payload['side'] ?? null) === 'b') {
                    $runningB = max(0, $runningB + $delta);
                }
            } elseif ($event->type === ScoreEventType::InningRun) {
                $runs = (int) ($payload['runs'] ?? 0);

                if (($payload['side'] ?? null) === 'a') {
                    $runningA += $runs;
                } elseif (($payload['side'] ?? null) === 'b') {
                    $runningB += $runs;
                }
            } elseif ($event->type === ScoreEventType::RoundScore) {
                $runningA += (int) ($payload['score_a'] ?? 0);
                $runningB += (int) ($payload['score_b'] ?? 0);
            }

            $rows[] = [
                'id' => $event->id,
                'description' => $this->describeEvent($event),
                'score_a' => $runningA,
                'score_b' => $runningB,
                'created_at' => $event->created_at?->format('g:i:s A'),
            ];
        }

        return array_slice(array_reverse($rows), 0, $limit);
    }

    private function describeEvent(ScoreEvent $event): string
    {
        $payload = $event->payload ?? [];

        $sideLabel = function (mixed $side): ?string {
            return match ($side) {
                'a' => $this->side_a_label,
                'b' => $this->side_b_label,
                default => null,
            };
        };

        return match ($event->type) {
            ScoreEventType::Point => sprintf(
                '%s%d — %s',
                ((int) ($payload['delta'] ?? 0)) >= 0 ? '+' : '',
                (int) ($payload['delta'] ?? 0),
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
            ),
            ScoreEventType::Correction => sprintf(
                'Correction: %s%d — %s%s',
                ((int) ($payload['delta'] ?? 0)) >= 0 ? '+' : '',
                (int) ($payload['delta'] ?? 0),
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                isset($payload['reason']) && $payload['reason'] !== ''
                    ? " ({$payload['reason']})"
                    : '',
            ),
            ScoreEventType::Foul => ($payload['action'] ?? null) === 'reset'
                ? 'Team fouls reset'
                : sprintf('Foul — %s', $sideLabel($payload['side'] ?? null) ?? 'Unknown'),
            ScoreEventType::PeriodChange => trim(implode(' — ', array_filter([
                isset($payload['period_label']) ? "Period: {$payload['period_label']}" : null,
                isset($payload['status_note']) ? (string) $payload['status_note'] : null,
            ]))) ?: 'Period updated',
            ScoreEventType::Paused => 'Game paused',
            ScoreEventType::Resumed => 'Game resumed',
            ScoreEventType::Ended => 'Game ended',
            ScoreEventType::RoundScore => sprintf(
                'Round %s: %s %d – %d %s',
                (string) ($payload['round'] ?? '?'),
                $this->side_a_label,
                (int) ($payload['score_a'] ?? 0),
                (int) ($payload['score_b'] ?? 0),
                $this->side_b_label,
            ),
            ScoreEventType::InningRun => sprintf(
                '+%d run%s — %s (Inning %s)',
                (int) ($payload['runs'] ?? 0),
                ((int) ($payload['runs'] ?? 0)) === 1 ? '' : 's',
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                (string) ($payload['inning'] ?? '?'),
            ),
            ScoreEventType::Count => match ($payload['action'] ?? null) {
                'out' => sprintf('Out (%d out%s this half)', (int) ($payload['outs'] ?? 0), ((int) ($payload['outs'] ?? 0)) === 1 ? '' : 's'),
                'ball' => sprintf('Ball (%d-%d)', (int) ($payload['balls'] ?? 0), (int) ($payload['strikes'] ?? 0)),
                'strike' => sprintf('Strike (%d-%d)', (int) ($payload['balls'] ?? 0), (int) ($payload['strikes'] ?? 0)),
                'reset_count' => 'Count reset',
                default => 'Count updated',
            },
            default => $event->type->label(),
        };
    }
}
