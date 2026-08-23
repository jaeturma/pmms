<?php

namespace App\Models;

use Database\Factories\MatchRosterPlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One athlete on a match's basketball roster (starter or bench), sourced
 * from a real Confirmed Entry — not free text. Persists independently of
 * any ScoringSession's lifecycle; who's currently on court right now is a
 * live-session concern tracked separately in `sport_state.on_court_a/b`.
 *
 * @property int $id
 * @property int $match_id
 * @property int $entry_id
 * @property string $side
 * @property string|null $jersey_number
 * @property bool $is_starter
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['match_id', 'entry_id', 'side', 'jersey_number', 'is_starter'])]
class MatchRosterPlayer extends Model
{
    /** @use HasFactory<MatchRosterPlayerFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_starter' => 'boolean',
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
     * @return BelongsTo<Entry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    /**
     * The full roster (both sides, all players — starters and bench) for a
     * match, on demand only: the "Substitute / manage roster" modal's own
     * `match-roster.show` fetch, never part of the live-polled payload
     * (`ScoringSession::onCourtPayload()` below is the lightweight one
     * that IS). Real relational data (name/jersey/photo), always freshly
     * queried, never cached inside sport_state's provisional JSON.
     *
     * @return array{a: array<int, array{id: int, name: string, jersey_number: string|null, is_starter: bool, photo_url: string|null}>, b: array<int, array{id: int, name: string, jersey_number: string|null, is_starter: bool, photo_url: string|null}>}
     */
    public static function payloadForMatch(int $matchId): array
    {
        $players = self::query()
            ->where('match_id', $matchId)
            ->with('entry.athlete')
            ->orderByDesc('is_starter')
            ->orderBy('jersey_number')
            ->get();

        return self::groupBySide($players);
    }

    /**
     * The same shape as `payloadForMatch()`, but scoped to a specific set
     * of roster player ids — `ScoringSession::onCourtPayload()` uses this
     * to keep the live-polled payload down to just the (at most 5-per-side)
     * players currently on court, instead of the whole roster.
     *
     * @param  array<int, int>  $ids
     * @return array{a: array<int, array{id: int, name: string, jersey_number: string|null, is_starter: bool, photo_url: string|null}>, b: array<int, array{id: int, name: string, jersey_number: string|null, is_starter: bool, photo_url: string|null}>}
     */
    public static function payloadForIds(array $ids): array
    {
        if ($ids === []) {
            return ['a' => [], 'b' => []];
        }

        $players = self::query()
            ->whereIn('id', $ids)
            ->with('entry.athlete')
            ->get();

        return self::groupBySide($players);
    }

    /**
     * @param  Collection<int, self>  $players
     * @return array{a: array<int, array{id: int, name: string, jersey_number: string|null, is_starter: bool, photo_url: string|null}>, b: array<int, array{id: int, name: string, jersey_number: string|null, is_starter: bool, photo_url: string|null}>}
     */
    private static function groupBySide($players): array
    {
        $describe = fn (self $player): array => [
            'id' => $player->id,
            'name' => $player->entry->athlete->fullName(),
            'jersey_number' => $player->jersey_number,
            'is_starter' => $player->is_starter,
            'photo_url' => $player->entry->athlete->photo_upload_id === null
                ? null
                : route('athletes.photo', $player->entry->athlete),
        ];

        return [
            'a' => $players->where('side', 'a')->map($describe)->values()->all(),
            'b' => $players->where('side', 'b')->map($describe)->values()->all(),
        ];
    }
}
