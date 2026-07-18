<?php

namespace App\Models;

use App\Enums\MeetStatus;
use Database\Factories\MeetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $school_year
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string|null $venue
 * @property MeetStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $events_count
 */
#[Fillable(['name', 'school_year', 'starts_at', 'ends_at', 'venue'])]
class Meet extends Model
{
    /** @use HasFactory<MeetFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'status' => MeetStatus::class,
        ];
    }

    /**
     * @return BelongsToMany<Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'meet_events')->withTimestamps();
    }

    /**
     * Registration-window hook for the delegation and entry modules.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->status === MeetStatus::RegistrationOpen;
    }
}
