<?php

namespace App\Models;

use App\Enums\SchoolLevel;
use Database\Factories\SchoolFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $district_id
 * @property int|null $school_district_id
 * @property string $name
 * @property string $school_id_code
 * @property SchoolLevel|null $level
 * @property string|null $school_type
 * @property string|null $address
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $delegations_count
 * @property-read int|null $athletes_count
 * @property-read int|null $personnel_count
 * @property-read int|null $entries_count
 */
#[Fillable(['district_id', 'school_district_id', 'name', 'school_id_code', 'school_type', 'level', 'address'])]
class School extends Model
{
    /** @use HasFactory<SchoolFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => SchoolLevel::class,
            'active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * The school's own school district — nullable, since most schools
     * won't have one assigned until an admin sets it up via the registry
     * (see SchoolDistrict's docblock).
     *
     * @return BelongsTo<SchoolDistrict, $this>
     */
    public function schoolDistrict(): BelongsTo
    {
        return $this->belongsTo(SchoolDistrict::class);
    }

    /**
     * @return HasMany<Delegation, $this>
     */
    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class);
    }

    /**
     * The school's own athletes — by their own home `school_id`, not by
     * which delegation registered them (a municipal delegation pools
     * several schools, so this is no longer reachable via Delegation).
     *
     * @return HasMany<Athlete, $this>
     */
    public function athletes(): HasMany
    {
        return $this->hasMany(Athlete::class);
    }

    /**
     * @return HasMany<Personnel, $this>
     */
    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    /**
     * @return HasManyThrough<Entry, Athlete, $this>
     */
    public function entries(): HasManyThrough
    {
        return $this->hasManyThrough(Entry::class, Athlete::class);
    }
}
