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
 * @property string|null $congressional_district
 * @property int|null $congressional_district_id
 * @property bool $active
 * @property int|null $logo_upload_id
 * @property int|null $team_logo_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $schools_count
 * @property-read FileUpload|null $logo
 * @property-read FileUpload|null $teamLogo
 * @property-read CongressionalDistrict|null $congressionalDistrict
 */
#[Fillable(['name', 'nickname', 'congressional_district', 'congressional_district_id'])]
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
     * The municipality's official crest/seal, shown on the public portal
     * wherever this district's name appears (nav, standings, live
     * scoreboards). `logo_upload_id` is intentionally kept out of Fillable
     * (like Athlete's photo_upload_id) — it's only ever set by
     * DistrictController after a successful upload, never via mass
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
     * The delegation's own team logo — distinct from the municipality's
     * official crest (`logo`/`logoUrl()`) — shown as the large mark on the
     * public Teams directory. `team_logo_upload_id` is kept out of
     * Fillable for the same reason `logo_upload_id` is: only
     * DistrictController sets it, after a successful upload.
     *
     * @return BelongsTo<FileUpload, $this>
     */
    public function teamLogo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'team_logo_upload_id');
    }

    /**
     * The public URL for this municipality's team logo, or `null` when
     * none has been uploaded.
     */
    public function teamLogoUrl(): ?string
    {
        return $this->team_logo_upload_id === null ? null : route('districts.team-logo', $this);
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

    /**
     * The province's legislative district this municipality belongs to.
     * `congressional_district` (the plain string column) remains the
     * authoritative value while both coexist — this FK is populated
     * alongside it by `DivisionRegistrySeeder` and is additive, not yet a
     * replacement (see `docs/architecture/pmms-data-migration-plan.md`
     * §6-7 for the coexistence/cutover plan).
     *
     * @return BelongsTo<CongressionalDistrict, $this>
     */
    public function congressionalDistrict(): BelongsTo
    {
        return $this->belongsTo(CongressionalDistrict::class);
    }
}
