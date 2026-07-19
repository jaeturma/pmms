<?php

namespace App\Models;

use Database\Factories\ResultPlacementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
#[Fillable(['event_result_id', 'entry_id', 'rank', 'mark', 'is_tie'])]
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
}
