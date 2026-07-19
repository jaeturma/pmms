<?php

namespace App\Models;

use App\Enums\ProtestStatus;
use Database\Factories\ProtestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A delegation's protest against an event result or a match. Exactly one
 * of event_result_id / match_id is set. Upholding a protest never changes
 * a result by itself — corrections go through the single WP-03-05 path.
 *
 * @property int $id
 * @property int $delegation_id
 * @property int|null $event_result_id
 * @property int|null $match_id
 * @property string $grounds
 * @property ProtestStatus $status
 * @property int|null $filed_by
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property string|null $remarks
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delegation_id', 'event_result_id', 'match_id', 'grounds'])]
class Protest extends Model
{
    /** @use HasFactory<ProtestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProtestStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * @return BelongsTo<EventResult, $this>
     */
    public function result(): BelongsTo
    {
        return $this->belongsTo(EventResult::class, 'event_result_id');
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
    public function filedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filed_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
