<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'meet_sport_id', 'event_id', 'sport_category_id', 'scope_type', 'delegation_id', 'school_id', 'status', 'review_notes', 'assigned_by', 'assigned_at', 'ended_at'])]
class CoachAssignmentRequest extends Model
{
    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime', 'assigned_at' => 'datetime', 'ended_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Explicitly load the account identity on audit/history screens. */
    public function userIncludingDeleted(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
