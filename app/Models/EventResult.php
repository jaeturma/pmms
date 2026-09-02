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
#[Fillable(['meet_id', 'event_id', 'match_id', 'event_schedule_id', 'scoring_session_id', 'result_source', 'result_scope'])]
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
            'version' => 'integer',
            'form_generated_version' => 'integer',
            'form_generated_at' => 'datetime',
            'tm_confirmed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'returned_at' => 'datetime',
            'official_at' => 'datetime',
        ];
    }

    public function scopeReal($query) { return $query->whereNull('demo_scenario_id'); }
    public function scopeDemo($query) { return $query->whereNotNull('demo_scenario_id'); }
    public function demoScenario(): BelongsTo { return $this->belongsTo(DemoScenario::class); }

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

    public function match(): BelongsTo
    {
        return $this->belongsTo(EventMatch::class, 'match_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(EventSchedule::class, 'event_schedule_id');
    }

    public function scoringSession(): BelongsTo
    {
        return $this->belongsTo(ScoringSession::class);
    }

    /**
     * @return HasMany<ResultPlacement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(ResultPlacement::class);
    }

    public function medalAwards(): HasMany
    {
        return $this->hasMany(MedalAward::class);
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

    public function officialBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'official_by');
    }

    public function tmConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tm_confirmed_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ResultAttachment::class);
    }

    public function currentSignedForm(): ?ResultAttachment
    {
        return $this->attachments()
            ->where('attachment_type', ResultAttachment::SIGNED_RESULT_FORM)
            ->where('result_version', $this->version)
            ->where('is_current', true)
            ->latest('id')
            ->first();
    }

    public function referenceNumber(): string
    {
        $code = strtoupper((string) ($this->event?->sport?->code ?: 'RESULT'));

        return sprintf('DDOPAA2026-%s-%06d', preg_replace('/[^A-Z0-9]+/', '-', $code), $this->id);
    }

    public function isLocked(): bool
    {
        return $this->status === ResultStatus::Official;
    }

    public function isFinalEventResult(): bool
    {
        return $this->result_scope === 'event';
    }

    public function isValidated(): bool
    {
        return in_array($this->status, [ResultStatus::Validated, ResultStatus::Official], true);
    }
}
