<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meet_sport_id', 'venue_id', 'competition_area_id', 'person_id', 'source_code',
    'is_lead', 'status', 'start_date', 'end_date', 'source_contact_text',
])]
class GameCoordinatorAssignment extends Model
{
    protected function casts(): array
    {
        return ['is_lead' => 'boolean', 'start_date' => 'date', 'end_date' => 'date'];
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function competitionArea(): BelongsTo
    {
        return $this->belongsTo(CompetitionArea::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
