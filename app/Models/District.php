<?php

namespace App\Models;

use Database\Factories\DistrictFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $nickname
 * @property bool $active
 * @property int|null $logo_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $schools_count
 * @property-read FileUpload|null $logo
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
     * The municipality's crest/logo, shown on the public portal wherever
     * this district's name appears. `logo_upload_id` is intentionally kept
     * out of Fillable (like Athlete's photo_upload_id) — it's only ever set
     * by DistrictController after a successful upload, never via mass
     * assignment from request input.
     *
     * @return BelongsTo<FileUpload, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'logo_upload_id');
    }

    /**
     * The public URL for this municipality's crest, or `null` when none
     * has been uploaded — the single source of truth every consumer
     * (admin registry, portal home, live scoreboards, standings) should
     * call rather than re-deriving the `logo_upload_id === null` check
     * and `route()` call themselves.
     */
    public function logoUrl(): ?string
    {
        return $this->logo_upload_id === null ? null : route('districts.logo', $this);
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
