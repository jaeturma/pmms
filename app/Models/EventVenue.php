<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['event_id', 'venue_id', 'playing_area_type', 'playing_area_count'])]
class EventVenue extends Model
{
    protected function casts(): array
    {
        return ['playing_area_count' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'event_venue_coordinators')->withTimestamps();
    }
}
