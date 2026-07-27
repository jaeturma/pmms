<?php

namespace App\Models;

use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $nickname
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $schools_count
 */
#[Fillable(['name', 'nickname'])]
class District extends Model
{
    /** @use HasFactory<DistrictFactory> */
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
     * @return HasMany<School, $this>
     */
    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }

    /**
     * This municipality's school districts (the real DepEd sub-unit —
     * see SchoolDistrict's docblock). Zero rows is a normal, common case:
     * a school-standings display should fall back to this municipality's
     * own name when there's no more than one.
     *
     * @return HasMany<SchoolDistrict, $this>
     */
    public function schoolDistricts(): HasMany
    {
        return $this->hasMany(SchoolDistrict::class);
    }
}
