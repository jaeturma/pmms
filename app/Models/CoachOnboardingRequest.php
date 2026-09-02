<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'meet_sport_id', 'delegation_id', 'school_id', 'district_id', 'event_id', 'profile_upload_id', 'certification_upload_id', 'status', 'submitted_at', 'review_notes'])]
class CoachOnboardingRequest extends Model
{
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'reviewed_at' => 'datetime'];
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

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'profile_upload_id');
    }

    public function certification(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'certification_upload_id');
    }
}
