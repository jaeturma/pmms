<?php

namespace App\Models;

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
        ];
    }
}
