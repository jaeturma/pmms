<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'district_id', 'event_id', 'profile_upload_id', 'certification_upload_id', 'status', 'review_notes'])]
class CoachOnboardingRequest extends Model
{
    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
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
