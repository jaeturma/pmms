<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meet_sport_id', 'sport_category_id', 'venue_id', 'competition_area_id',
    'source_code', 'status',
])]
class SportCategoryCompetitionArea extends Model
{
    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function sportCategory(): BelongsTo
    {
        return $this->belongsTo(SportCategory::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function competitionArea(): BelongsTo
    {
        return $this->belongsTo(CompetitionArea::class);
    }
}
