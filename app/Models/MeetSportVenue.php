<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'meet_sport_id', 'venue_id', 'source_code', 'expected_area_count', 'notes',
    'source_area_text', 'source_coordinator_text', 'source_contact_text',
    'import_status', 'display_order', 'status',
])]
class MeetSportVenue extends Model
{
    protected function casts(): array
    {
        return ['expected_area_count' => 'integer', 'display_order' => 'integer'];
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function coordinators(): HasMany
    {
        return $this->hasMany(GameCoordinatorAssignment::class, 'venue_id', 'venue_id')
            ->where('meet_sport_id', $this->meet_sport_id);
    }
}
