<?php

namespace App\Models;

use Database\Factories\SportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $short_description
 * @property string|null $description
 * @property int|null $photo_upload_id
 * @property int|null $tournament_manager_id
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $events_count
 */
#[Fillable(['code', 'name', 'slug', 'classification', 'short_description', 'description', 'icon_key', 'competition_format', 'is_team_sport', 'active', 'display_order'])]
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
            'is_team_sport' => 'boolean',
            'display_order' => 'integer',
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
     * `duty` (e.g. "Referee", "Scorer", "Timekeeper") is an optional,
     * free-text label an admin can set per assignment for public display —
     * `null` renders as the generic "Technical Official" on the mini
     * portal rather than fabricating a duty that wasn't actually recorded.
     *
     * @return BelongsToMany<User, $this>
     */
    public function technicalOfficials(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('duty')->withTimestamps();
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

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_upload_id');
    }

    /**
     * A real, approved sports photo for this sport's public mini portal —
     * `null` until an admin uploads one (no photo is fabricated/hotlinked
     * as a placeholder). `photo_upload_id` is intentionally kept out of
     * `#[Fillable]`, same convention as `District::logo_upload_id` — set
     * only by a controller that has already validated the upload, never
     * via mass assignment.
     */
    public function photoUrl(): ?string
    {
        return $this->photo_upload_id === null ? null : route('sports.photo', $this);
    }

    /**
     * The one user (if any) holding the catalog-wide `TournamentManager`
     * login role for this sport — see the migration's own docblock for why
     * this is a nullable FK rather than a pivot.
     *
     * @return BelongsTo<User, $this>
     */
    public function tournamentManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tournament_manager_id');
    }
}
