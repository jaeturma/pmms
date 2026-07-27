<?php

namespace App\Models;

use Database\Factories\SchoolDistrictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $district_id
 * @property string $name
 * @property string|null $nickname
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $schools_count
 */
#[Fillable(['district_id', 'name', 'nickname'])]
class SchoolDistrict extends Model
{
    /** @use HasFactory<SchoolDistrictFactory> */
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
     * The municipality this school district belongs to. `district_id`
     * means "municipality" here, matching the convention already
     * established on School/Delegation — see docs/division.md.
     *
     * @return BelongsTo<District, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    /**
     * @return HasMany<School, $this>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
