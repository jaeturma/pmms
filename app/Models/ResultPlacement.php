<?php

namespace App\Models;

use Database\Factories\ResultPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One placed entry in an event's final standing.
 *
 * @property int $id
 * @property int $event_result_id
 * @property int $entry_id
 * @property int $rank
 * @property string|null $mark
 * @property bool $is_tie
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['athlete_id', 'event_result_id', 'entry_id', 'team_entry_id', 'delegation_id', 'rank', 'mark', 'result_value', 'tally_quantity', 'is_tie'])]
class ResultPlacement extends Model
{
    /** @use HasFactory<ResultPlacementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'result_value' => 'decimal:6',
            'tally_quantity' => 'integer',
            'is_tie' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<EventResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(EventResult::class, 'event_result_id');
    }

    /**
     * @return BelongsTo<Entry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }

    public function teamEntry(): BelongsTo
    {
        return $this->belongsTo(TeamEntry::class);
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class)->withTrashed();
    }

    public function reportingAthletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class, 'result_placement_athlete')->withTrashed();
    }

    public function reportingCoaches(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'result_placement_coach')->withPivot('role')->withTrashed();
    }

    public function medalAward(): HasOne
    {
        return $this->hasOne(MedalAward::class);
    }
}
