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
     * `$operational` is set only for the internal operator console
     * (`ScoringSessionController::board()`/`show()`). It adds the
     * backend-only operational remarks describing a manual setup or a
     * participant override; the public scoreboard never receives them
     * (spec §12/§13) and never sees the raw `sport_state.participants`
     * provenance block either — that key is stripped from every payload.
     *
     * @return array<string, mixed>
     */
    public function toLivePayload(bool $operational = false): array
    {
        [$sideAAthlete, $sideBAthlete] = $this->athleteParticipants();

        $sportState = $this->sport_state;
        if (is_array($sportState) && array_key_exists('participants', $sportState)) {
            unset($sportState['participants']);

            // A session whose only stored state was the participant
            // provenance (e.g. a manual generic-board setup) still reports
            // `sport_state` as null, exactly as it did before — the
            // provenance is bookkeeping, not board state.
            if ($sportState === []) {
                $sportState = null;
            }
        }

        if (! $operational && is_array($sportState)) {
            $sportState = $this->redactLiveJudgeScores($sportState);
        }

        return [
            'id' => $this->id,
            'match_id' => $this->match_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'scoreboard_mode' => $this->sport_state['scoreboard_mode'] ?? null,
            'side_a_label' => $this->side_a_label,
            'side_b_label' => $this->side_b_label,
            'side_a_team' => $this->districtNicknameFor($this->side_a_label),
            'side_b_team' => $this->districtNicknameFor($this->side_b_label),
            'side_a_logo_url' => $this->districtLogoUrl($this->side_a_label),
            'side_b_logo_url' => $this->districtLogoUrl($this->side_b_label),
            'side_a_athlete' => $sideAAthlete,
            'side_b_athlete' => $sideBAthlete,
            'score_a' => $this->score_a,
            'score_b' => $this->score_b,
            'period_label' => $this->period_label,
            'status_note' => $this->status_note,
            'board_type' => $this->boardType()->value,
            'sport_state' => $sportState,
            'onCourt' => $this->onCourtPayload(),
            'playByPlay' => $this->playByPlay(),
            'started_at' => $this->started_at?->toIso8601String(),
            'elapsed_seconds' => $this->activeElapsedSeconds(),
            'clock_running' => $this->status === ScoringSessionStatus::InProgress,
            ...($operational ? [
                'operational_remarks' => $this->operationalRemarks(),
                'participant_provenance' => $this->participantProvenance(),
            ] : []),
        ];
    }

    /**
     * Boxing only: unless the meet has opted into live judge-score
     * disclosure (`sport_state.show_live_judge_scores`), the per-judge
     * card breakdown and the provisional computed decision are withheld
     * from the PUBLIC payload while the bout is still running — many
     * amateur-boxing bodies forbid revealing judges' running cards
     * mid-bout. The round-by-round consensus line, the unofficial points
     * total and the deduction tallies stay visible; the operator console
     * (`$operational`) always sees everything. Full disclosure the moment
     * the session ends.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private function redactLiveJudgeScores(array $state): array
    {
        $isBoxing = $this->boardType() === ScoreboardType::Boxing;
        $ended = $this->status === ScoringSessionStatus::Ended;
        $disclosed = (bool) ($state['show_live_judge_scores'] ?? false);

        $hasJudgeData = ($state['judge_rounds'] ?? []) !== []
            || (is_array($state['decision'] ?? null) && ($state['decision']['manual'] ?? false) !== true);

        if (! $isBoxing || $ended || $disclosed || ! $hasJudgeData) {
            return $state;
        }

        $state['judge_rounds'] = collect($state['judge_rounds'] ?? [])
            ->map(fn ($round): array => ['round' => (int) ($round['round'] ?? 0), 'cards_hidden' => true])
            ->all();
        $state['judge_scores_hidden'] = true;

        // A computed (provisional) decision leaks the per-judge tally, so
        // it's withheld entirely; a manual referee decision (RSC/KO/DSQ/
        // WO) is a public announcement and stays — but its per-judge
        // breakdown (`tally.judges`) is still stripped, only the announced
        // count (e.g. "5-0") survives.
        if (is_array($state['decision'] ?? null)) {
            if (($state['decision']['manual'] ?? false) !== true) {
                $state['decision'] = null;
            } elseif (is_array($state['decision']['tally'] ?? null)) {
                unset($state['decision']['tally']['judges']);
            }
        }

        return $state;
    }

    /**
     * The `sport_state.participants` bookkeeping block, if a manual setup
     * or an override ever wrote one — the record of who supplied the
     * scoreboard's operational participant data and what it replaced
     * (spec §11/§12). Never part of the public payload.
     *
     * @return array<string, mixed>|null
     */
    public function participantProvenance(): ?array
    {
        $participants = $this->sport_state['participants'] ?? null;

        return is_array($participants) ? $participants : null;
    }

    /**
     * Short backend-only notices for the operator console when the
     * scoreboard is running on manually-supplied or overridden
     * participant data (spec §12). Deliberately terse and non-alarming —
     * the board itself is working normally.
     *
     * @return array<int, string>
     */
    public function operationalRemarks(): array
    {
        $provenance = $this->participantProvenance();

        if ($provenance === null) {
            return [];
        }

        return match ($provenance['mode'] ?? null) {
            'manual_setup' => [__('Manual scoreboard setup — participant data entered by the Tournament ICT.')],
            'override' => array_values(array_filter([
                __('Participants overridden by Tournament ICT.'),
                isset($provenance['previous']['a'], $provenance['previous']['b'])
                    ? __('Generated participants were: :a vs :b.', [
                        'a' => $provenance['previous']['a'] ?: __('Data incomplete'),
                        'b' => $provenance['previous']['b'] ?: __('Data incomplete'),
                    ])
                    : null,
                ($provenance['reason'] ?? null) ? __('Reason: :reason', ['reason' => $provenance['reason']]) : null,
            ])),
            default => [],
        };
    }

    /**
     * The (at most 5-per-side) players currently on court — deliberately
     * NOT the whole roster. This is embedded in the live-polled payload
     * (every 5s, plus every Reverb push), so keeping it to just what the
     * score/foul buttons need to render is the point: the full roster
     * (bench included) is real data too, but it's fetched on demand only
     * by the substitution modal (`MatchRosterController::show()`), never
     * baked into the hot polling path.
     *
     * @return array{a: array<int, array<string, mixed>>, b: array<int, array<string, mixed>>}
     */
    public function onCourtPayload(): array
    {
        $state = $this->sport_state ?? [];
        $ids = [...($state['on_court_a'] ?? []), ...($state['on_court_b'] ?? [])];

        return MatchRosterPlayer::payloadForIds($ids);
    }

    /**
     * A side's logo for live play, matched by name — `side_a_label`/
     * `side_b_label` are freeform text entered when the session started
     * (never an FK to `District`), but they're conventionally a
     * delegation's `registrantName()`, which for a Province division IS
     * the municipality's own name. A City division's school-named side, or
     * any label that doesn't match a district, simply gets no logo here —
     * `MunicipalityCrest` already falls back to its initials badge.
     *
     * Prefers the delegation's own team logo (`District::teamLogo`) over
     * the municipality's official crest (`District::logo`) — the team logo
     * is what a delegation competes under, the crest falls back only when
     * no team logo has been uploaded yet.
     */
    private function districtLogoUrl(?string $label): ?string
    {
        $district = $this->districtForLabel($label);

        return $district?->teamLogoUrl() ?? $district?->logoUrl();
    }

    /**
     * Resolve a freeform side label to its `District` (the app's "team").
     * The label is conventionally a delegation's `registrantName()` — the
     * municipality/district name — but a manual or overridden setup may
     * append "– Athlete Name", so this tries the whole label first, then
     * the part before an en/em dash. Returns `null` for a label that
     * matches no district (e.g. a City division's school-named side).
     */
    private function districtForLabel(?string $label): ?District
    {
        if ($label === null || $label === '') {
            return null;
        }

        $candidates = [trim($label)];
        foreach (['–', '—', ' - '] as $separator) {
            if (str_contains($label, $separator)) {
                $candidates[] = trim(explode($separator, $label, 2)[0]);
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            if ($candidate === '') {
                continue;
            }
            $district = District::query()->where('name', $candidate)->first();
            if ($district !== null) {
                return $district;
            }
        }

        return null;
    }

    /**
     * A side's team name for a broadcast title — the `District` nickname
     * when one is set, otherwise the raw side label (so a side that maps
     * to no district, or to a district with no nickname, still shows
     * something sensible).
     */
    private function districtNicknameFor(?string $label): ?string
    {
        if ($label === null || $label === '') {
            return null;
        }

        $nickname = $this->districtForLabel($label)?->nickname;

        return ($nickname !== null && $nickname !== '') ? $nickname : $label;
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

        if ($this->match?->event === null || $this->match->event->is_team_event) {
            return [null, null];
        }

        $entries = $this->match->entries;

        if ($entries->count() !== 2) {
            return [null, null];
        }

        $describe = fn (Entry $entry): array => [
            'name' => $entry->athlete?->fullName() ?? __('Missing athlete'),
            'sports_photo_url' => $entry->athlete?->sportsPhotoUrl(),
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
     * @return array<int, array{id: int, description: string, score_a: int, score_b: int, created_at: string|null, removable: bool}>
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
            } elseif ($event->type === ScoreEventType::WrestlingPoint) {
                // Wrestling's own points-scored event — additive like
                // Point above, just under a real move-specific type
                // instead of the generic one (a wrestling correction
                // still goes through the generic Correction type/branch
                // above, since wrestling's score_a/b is a plain running
                // total with no risk of misreading it the way rally-
                // point/game-point sports' corrections had to avoid).
                $points = (int) ($payload['points'] ?? 0);

                if (($payload['side'] ?? null) === 'a') {
                    $runningA += $points;
                } elseif (($payload['side'] ?? null) === 'b') {
                    $runningB += $points;
                }
            } elseif ($event->type === ScoreEventType::InningRun) {
                $runs = (int) ($payload['runs'] ?? 0);

                if (($payload['side'] ?? null) === 'a') {
                    $runningA += $runs;
                } elseif (($payload['side'] ?? null) === 'b') {
                    $runningB += $runs;
                }
            } elseif (in_array($event->type, [ScoreEventType::RoundScore, ScoreEventType::JudgeRound], true)) {
                // JudgeRound carries the same `score_a`/`score_b` keys as
                // RoundScore — the round's consensus line (the modal judge
                // card among all judges), not an average — so the running
                // "unofficial points total" reconstructs identically.
                $runningA += (int) ($payload['score_a'] ?? 0);
                $runningB += (int) ($payload['score_b'] ?? 0);
            } elseif ($event->type === ScoreEventType::SetComplete) {
                // Volleyball/sepak takraw: the running score here is sets
                // won, not points — `payload` already carries the exact
                // new totals (not a delta), unlike RoundScore/InningRun
                // above, so this assigns rather than adds.
                $runningA = (int) ($payload['sets_won_a'] ?? $runningA);
                $runningB = (int) ($payload['sets_won_b'] ?? $runningB);
            } elseif ($event->type === ScoreEventType::GameComplete) {
                // Table tennis/badminton: same "assign the real total,
                // don't add a delta" reasoning as SetComplete above — the
                // running score here is games won.
                $runningA = (int) ($payload['games_won_a'] ?? $runningA);
                $runningB = (int) ($payload['games_won_b'] ?? $runningB);
            } elseif ($event->type === ScoreEventType::RackComplete) {
                // Billiard: the running score here is racks won, not
                // points — same "assign the real total" reasoning as
                // SetComplete/GameComplete above.
                $runningA = (int) ($payload['racks_won_a'] ?? $runningA);
                $runningB = (int) ($payload['racks_won_b'] ?? $runningB);
            } elseif ($event->type === ScoreEventType::RackUndo) {
                $runningA = (int) ($payload['racks_won_a'] ?? $runningA);
                $runningB = (int) ($payload['racks_won_b'] ?? $runningB);
            } elseif (in_array($event->type, [ScoreEventType::EndComplete, ScoreEventType::EndUndo], true)) {
                // Bocce: the payload already carries the real post-event
                // totals for both sides (an end always resolves to only
                // one side scoring, but recording both keeps this branch
                // symmetric with every other "assign, don't add" case
                // above), so this assigns rather than adds.
                $runningA = (int) ($payload['score_a'] ?? $runningA);
                $runningB = (int) ($payload['score_b'] ?? $runningB);
            } elseif ($event->type === ScoreEventType::TennisUndo) {
                // Re-asserts the real post-undo sets-won total (the
                // ScoringSessionController::tennisUndo() payload always
                // carries it) — safe to apply unconditionally, whether or
                // not the undone point had also completed a set, since
                // this is the same "assign, don't add" pattern as
                // SetComplete/GameComplete above.
                $runningA = (int) ($payload['sets_won_a'] ?? $runningA);
                $runningB = (int) ($payload['sets_won_b'] ?? $runningB);
            }

            $rows[] = [
                'id' => $event->id,
                'description' => $this->describeEvent($event),
                'score_a' => $runningA,
                'score_b' => $runningB,
                'created_at' => $event->created_at?->format('g:i:s A'),
                'removable' => $event->type === ScoreEventType::Point
                    || ($event->type === ScoreEventType::Foul && ($payload['action'] ?? null) === 'add'),
            ];
        }

        return array_slice(array_reverse($rows), 0, $limit);
    }

    /**
     * Formats a tennis game's raw point counters as the real Love/15/30/
     * 40/Deuce/Ad display — the raw counters (see
     * `ScoringSessionController::applyTennisPoint()`) only ever exceed 3
     * while both sides are tied at 3+ (deuce territory), so this only
     * needs to special-case that range.
     */
    private function formatTennisPoints(int $a, int $b): string
    {
        if ($a >= 3 && $b >= 3) {
            if ($a === $b) {
                return 'Deuce';
            }

            return $a > $b
                ? "Ad {$this->side_a_label}"
                : "Ad {$this->side_b_label}";
        }

        $labels = ['Love', '15', '30', '40'];

        return sprintf('%s-%s', $labels[min($a, 3)], $labels[min($b, 3)]);
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
            ScoreEventType::Note => isset($payload['message']) && $payload['message'] !== ''
                ? (string) $payload['message']
                : 'Operational note',
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
            ScoreEventType::JudgeRound => sprintf(
                'Round %s judged: %s %d–%d %s (judges %d–%d)',
                (string) ($payload['round'] ?? '?'),
                $this->side_a_label,
                (int) ($payload['score_a'] ?? 0),
                (int) ($payload['score_b'] ?? 0),
                $this->side_b_label,
                (int) ($payload['judges_a'] ?? 0),
                (int) ($payload['judges_b'] ?? 0),
            ),
            ScoreEventType::Deduction => ($payload['action'] ?? null) === 'reset'
                ? 'Point deductions reset'
                : sprintf(
                    '−%d point%s — %s (deduction)',
                    (int) ($payload['points'] ?? 1),
                    ((int) ($payload['points'] ?? 1)) === 1 ? '' : 's',
                    $sideLabel($payload['side'] ?? null) ?? 'Unknown',
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
            ScoreEventType::Bell => 'Bell sounded',
            ScoreEventType::RallyPoint => sprintf(
                '%s%s%d — %s (Set %s: %d-%d)',
                ($payload['is_correction'] ?? false) ? 'Correction: ' : '',
                ((int) ($payload['delta'] ?? 0)) >= 0 ? '+' : '',
                (int) ($payload['delta'] ?? 0),
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                (string) ($payload['set'] ?? '?'),
                (int) ($payload['current_set_score_a'] ?? 0),
                (int) ($payload['current_set_score_b'] ?? 0),
            ),
            ScoreEventType::SetComplete => sprintf(
                'Set %s: %s %d – %d %s (leads %d-%d)',
                (string) ($payload['set'] ?? '?'),
                $this->side_a_label,
                (int) ($payload['score_a'] ?? 0),
                (int) ($payload['score_b'] ?? 0),
                $this->side_b_label,
                (int) ($payload['sets_won_a'] ?? 0),
                (int) ($payload['sets_won_b'] ?? 0),
            ),
            ScoreEventType::Card => ($payload['action'] ?? null) === 'reset'
                ? 'Cards reset'
                : sprintf(
                    '%s card — %s',
                    ucfirst((string) ($payload['type'] ?? 'unknown')),
                    $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                ),
            ScoreEventType::PenaltyThrow => ($payload['action'] ?? null) === 'reset'
                ? 'Penalty throws reset'
                : sprintf(
                    'Penalty throw — %s',
                    $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                ),
            ScoreEventType::GamePoint => sprintf(
                '%s%s%d — %s (Game %s: %d-%d)',
                ($payload['is_correction'] ?? false) ? 'Correction: ' : '',
                ((int) ($payload['delta'] ?? 0)) >= 0 ? '+' : '',
                (int) ($payload['delta'] ?? 0),
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                (string) ($payload['game'] ?? '?'),
                (int) ($payload['current_game_score_a'] ?? 0),
                (int) ($payload['current_game_score_b'] ?? 0),
            ),
            ScoreEventType::GameComplete => sprintf(
                'Game %s: %s %d – %d %s (leads %d-%d)',
                (string) ($payload['game'] ?? '?'),
                $this->side_a_label,
                (int) ($payload['score_a'] ?? 0),
                (int) ($payload['score_b'] ?? 0),
                $this->side_b_label,
                (int) ($payload['games_won_a'] ?? 0),
                (int) ($payload['games_won_b'] ?? 0),
            ),
            ScoreEventType::WrestlingPoint => sprintf(
                '+%d %s — %s',
                (int) ($payload['points'] ?? 0),
                match ($payload['move'] ?? null) {
                    'takedown' => 'Takedown',
                    'escape' => 'Escape',
                    'reversal' => 'Reversal',
                    'near_fall' => 'Near fall',
                    'penalty' => 'Penalty',
                    default => 'Point',
                },
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
            ),
            ScoreEventType::Fall => ($payload['action'] ?? null) === 'clear'
                ? 'Fall cleared'
                : sprintf('Fall — %s', $sideLabel($payload['side'] ?? null) ?? 'Unknown'),
            ScoreEventType::TennisPoint => sprintf(
                '+1 — %s (Games %d-%d, %s)',
                $sideLabel($payload['side'] ?? null) ?? 'Unknown',
                (int) ($payload['current_set_games_a'] ?? 0),
                (int) ($payload['current_set_games_b'] ?? 0),
                ($payload['is_tiebreak'] ?? false)
                    ? 'Tiebreak'
                    : $this->formatTennisPoints(
                        (int) ($payload['current_game_points_a'] ?? 0),
                        (int) ($payload['current_game_points_b'] ?? 0),
                    ),
            ),
            ScoreEventType::TennisUndo => 'Last point undone',
            ScoreEventType::RackComplete => sprintf(
                'Rack %s: %s wins (leads %d-%d)',
                (string) ($payload['rack'] ?? '?'),
                $sideLabel($payload['winner'] ?? null) ?? 'Unknown',
                (int) ($payload['racks_won_a'] ?? 0),
                (int) ($payload['racks_won_b'] ?? 0),
            ),
            ScoreEventType::RackUndo => 'Last rack undone',
            ScoreEventType::EndComplete => sprintf(
                'End %s: %s +%d (score %d-%d)',
                (string) ($payload['end'] ?? '?'),
                $sideLabel($payload['winner'] ?? null) ?? 'Unknown',
                (int) ($payload['points'] ?? 0),
                (int) ($payload['score_a'] ?? 0),
                (int) ($payload['score_b'] ?? 0),
            ),
            ScoreEventType::EndUndo => 'Last end undone',
            default => $event->type->label(),
        };
    }
}
