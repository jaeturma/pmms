<?php

namespace App\Models;

use App\Enums\EligibilityResult;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meet_id', 'subject_type', 'subject_id', 'sport_id', 'sport_category_id', 'event_id', 'result', 'checked_by', 'checked_at', 'snapshot'])]
class EligibilityCheck extends Model
{
    protected function casts(): array
    {
        return ['result' => EligibilityResult::class, 'checked_at' => 'datetime', 'snapshot' => 'array'];
    }

    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class, 'sport_category_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
