<?php

namespace App\Models;

use Database\Factories\MatchRosterPlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
     * The one shape every consumer (the operator console's Inertia props,
     * ScoringSession::toLivePayload()'s live roster) reads — real
     * relational data (name/jersey/photo), always freshly queried, never
     * cached inside sport_state's provisional JSON.
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
