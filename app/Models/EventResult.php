<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Database\Factories\EventResultFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The final standing of one meet event. Encoded results are working data;
 * only validated results are official (and feed the medal tally).
 *
 * @property int $id
 * @property int $meet_id
 * @property int $event_id
 * @property ResultStatus $status
 * @property int|null $encoded_by
 * @property Carbon $encoded_at
 * @property int|null $validated_by
 * @property Carbon|null $validated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'event_id'])]
class EventResult extends Model
{
    /** @use HasFactory<EventResultFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ResultStatus::class,
            'encoded_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<ResultPlacement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(ResultPlacement::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function encodedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function isValidated(): bool
    {
        return $this->status === ResultStatus::Validated;
    }
}
