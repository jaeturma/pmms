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
        [$sideAAthlete, $sideBAthlete] = $this->athleteParticipants();

        return [
            'id' => $this->id,
            'match_id' => $this->match_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'side_a_label' => $this->side_a_label,
            'side_b_label' => $this->side_b_label,
            'side_a_logo_url' => $this->districtLogoUrl($this->side_a_label),
            'side_b_logo_url' => $this->districtLogoUrl($this->side_b_label),
            'side_a_athlete' => $sideAAthlete,
            'side_b_athlete' => $sideBAthlete,
            'score_a' => $this->score_a,
            'score_b' => $this->score_b,
            'period_label' => $this->period_label,
            'status_note' => $this->status_note,
            'board_type' => $this->boardType()->value,
            'sport_state' => $this->sport_state,
            'roster' => MatchRosterPlayer::payloadForMatch($this->match_id),
            'playByPlay' => $this->playByPlay(),
            'started_at' => $this->started_at?->toIso8601String(),
            'elapsed_seconds' => $this->activeElapsedSeconds(),
            'clock_running' => $this->status === ScoringSessionStatus::InProgress,
        ];
    }

    /**
     * A side's real crest, matched by name — `side_a_label`/`side_b_label`
     * are freeform text entered when the session started (never an FK to
     * `District`), but they're conventionally a delegation's
     * `registrantName()`, which for a Province division IS the
     * municipality's own name. A City division's school-named side, or
     * any label that doesn't match a district, simply gets no logo here —
     * `MunicipalityCrest` already falls back to its initials badge.
     */
    private function districtLogoUrl(?string $label): ?string
    {
        if ($label === null || $label === '') {
            return null;
        }

        return District::query()->where('name', $label)->first()?->logoUrl();
    }

    /**
     * The two individual athletes fighting this bout, keyed positionally
     * to side A/side B — same convention `ScoringSessionController::
     * matchParticipants()` already uses for the operator console. Only
     * meaningful for a one-on-one individual event (boxing today); a team
     * event's `match.entries` is a full roster, not two corners, so this
     * intentionally returns `[null, null]` unless there are exactly two.
     * `side_a_label`/`side_b_label` stay the free-text display line
     * regardless — this only adds the athlete's name/sports photo on top
     * of it when one can be resolved.
     *
     * @return array{0: array{name: string, sports_photo_url: ?string}|null, 1: array{name: string, sports_photo_url: ?string}|null}
     */
    private function athleteParticipants(): array
    {
        $this->loadMissing('match.event', 'match.entries.athlete');

        if ($this->match->event->is_team_event) {
            return [null, null];
        }

        $entries = $this->match->entries;

        if ($entries->count() !== 2) {
            return [null, null];
        }

        $describe = fn (Entry $entry): array => [
            'name' => $entry->athlete->fullName(),
            'sports_photo_url' => $entry->athlete->sportsPhotoUrl(),
        ];

        return [$describe($entries[0]), $describe($entries[1])];
    }

    /**
     * Real running-clock seconds, excluding any paused time — reconstructed
     * from the append-only `score_events` log (same source-of-truth
     * approach `playByPlay()` already uses for the running score), not a
     * separately stored/drifting counter. Frozen at whatever it was on the
     * last `Paused` event while the session is currently paused; frozen at
     * `ended_at` once ended.
     */
    public function activeElapsedSeconds(): int
    {
        if ($this->started_at === null) {
            return 0;
        }

        $endpoint = match ($this->status) {
            ScoringSessionStatus::Ended => $this->ended_at ?? now(),
            default => now(),
        };

        $cursor = $this->started_at;
        $seconds = 0;

        foreach ($this->events()->whereIn('type', [ScoreEventType::Paused->value, ScoreEventType::Resumed->value])->oldest('id')->get() as $event) {
            if ($event->type === ScoreEventType::Paused && $cursor !== null) {
                $seconds += (int) $cursor->diffInSeconds($event->created_at);
                $cursor = null;
            } elseif ($event->type === ScoreEventType::Resumed && $cursor === null) {
                $cursor = $event->created_at;
            }
        }

        if ($cursor !== null) {
            $seconds += (int) $cursor->diffInSeconds($endpoint);
        }

        return max(0, $seconds);
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

        // A basketball point/foul attributed to a specific roster player
        // (WP live-basketball) denormalizes the name into the payload at
        // write time — never joined back from match_roster_players here,
        // so an old play-by-play line still reads correctly even if that
        // roster row is later removed.
        $playerSuffix = isset($payload['player_name']) && $payload['player_name'] !== ''
            ? " ({$payload['player_name']})"
            : '';

        return match ($event->type) {
            ScoreEventType::Point => sprintf(
                '%s%d — %s%s',
                ((int) ($payload['delta'] ?? 0)) >= 0 ? '+' : '',
                (int) ($payload['delta'] ?? 0),
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                $playerSuffix,
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
                : sprintf('Foul — %s%s', $sideLabel($payload['side'] ?? null) ?? 'Unknown', $playerSuffix),
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
            ScoreEventType::Possession => isset($payload['side'])
                ? sprintf('Possession: %s', $sideLabel($payload['side']) ?? 'Unknown')
                : 'Possession cleared',
            ScoreEventType::Substitution => sprintf(
                '%s: %s (%s)',
                ($payload['on_court'] ?? false) ? 'IN' : 'OUT',
                (string) ($payload['player_name'] ?? 'Unknown'),
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
            ),
            ScoreEventType::Horn => 'Horn sounded',
            default => $event->type->label(),
        };
    }
}
