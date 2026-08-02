<?php

namespace App\Models;

use Database\Factories\SportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $events_count
 */
#[Fillable(['name'])]
class Sport extends Model
{
    /** @use HasFactory<SportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function technicalOfficials(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * This sport's inclusion across meets — see `MeetSport`'s own
     * docblock. `Sport` itself remains a global, meet-unscoped catalog
     * row; this is the new per-meet join.
     *
     * @return HasMany<MeetSport, $this>
     */
    public function meetSports(): HasMany
    {
        return $this->hasMany(MeetSport::class);
    }

    /**
     * @return HasMany<SportCategory, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(SportCategory::class);
    }
}
