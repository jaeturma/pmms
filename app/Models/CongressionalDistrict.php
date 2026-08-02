<?php

namespace App\Models;

use Database\Factories\CongressionalDistrictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The province's legislative districts (e.g. "First", "Second") — each
 * groups one or more municipalities (`District` rows). Kept as its own
 * table/model rather than reusing `District` for both concepts, since
 * `District` already means "municipality" throughout this codebase (see
 * `District`'s own docblock and `SchoolDistrict::municipality()`) —
 * overloading it here would recreate the exact naming collision
 * `SchoolDistrict` was already named distinctly to avoid.
 *
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $districts_count
 */
#[Fillable(['name'])]
class CongressionalDistrict extends Model
{
    /** @use HasFactory<CongressionalDistrictFactory> */
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
     * @return HasMany<District, $this>
     */
    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}
