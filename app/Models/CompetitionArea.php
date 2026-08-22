<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['venue_id', 'source_code', 'code', 'name', 'area_type', 'display_order', 'status', 'notes'])]
class CompetitionArea extends Model
{
    protected function casts(): array
    {
        return ['display_order' => 'integer'];
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class);
    }
}
