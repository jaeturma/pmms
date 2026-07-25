<?php

namespace App\Models;

use App\Enums\ScoreEventType;
use Database\Factories\ScoreEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Append-only log of everything that happened in a scoring session — the
 * audit trail and the mechanism a reconnecting client catches up with.
 * Never updated after creation.
 *
 * @property int $id
 * @property int $scoring_session_id
 * @property ScoreEventType $type
 * @property array<string, mixed>|null $payload
 * @property int|null $recorded_by
 * @property Carbon|null $created_at
 */
#[Fillable(['scoring_session_id', 'type', 'payload', 'recorded_by'])]
class ScoreEvent extends Model
{
    /** @use HasFactory<ScoreEventFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ScoreEventType::class,
            'payload' => 'array',
        ];
    }

    /**
     * @return BelongsTo<ScoringSession, $this>
     */
    public function scoringSession(): BelongsTo
    {
        return $this->belongsTo(ScoringSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
