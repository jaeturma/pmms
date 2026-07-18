<?php

namespace App\Models;

use App\Enums\SchoolLevel;
use Database\Factories\SchoolFactory;
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
 * @property string $school_id_code
 * @property SchoolLevel $level
 * @property string|null $address
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['district_id', 'name', 'school_id_code', 'level', 'address'])]
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
     * @return HasMany<Delegation, $this>
     */
    public function delegations(): HasMany
    {
        return $this->hasMany(Delegation::class);
    }
}
