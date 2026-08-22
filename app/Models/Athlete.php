<?php

namespace App\Models;

use App\Enums\AgeDivision;
use App\Enums\Sex;
use Database\Factories\AthleteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delegation_id
 * @property int $school_id
 * @property string $first_name
 * @property string $last_name
 * @property Sex $sex
 * @property Carbon $birthdate
 * @property string $lrn
 * @property int $grade_level
 * @property int|null $photo_upload_id
 * @property int|null $sports_photo_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delegation_id', 'school_id', 'first_name', 'middle_name', 'last_name', 'name_extension', 'sex', 'birthdate', 'lrn', 'grade_level'])]
class Athlete extends Model
{
    /** @use HasFactory<AthleteFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sex' => Sex::class,
            'birthdate' => 'date',
            'grade_level' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Delegation, $this>
     */
    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    /**
     * The athlete's own home school — set once at registration, decoupled
     * from the delegation's registering unit (a municipal delegation pools
     * several schools). Never infer "school" from the delegation for an
     * individual; use this instead.
     *
     * @return BelongsTo<School, $this>
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_upload_id');
    }

    /**
     * The action/competition photo used on live scoreboards (e.g. a boxing
     * bout's corner display) — distinct from `photo`, the registry/ID-card
     * portrait. Both are optional and independently replaceable.
     *
     * @return BelongsTo<FileUpload, $this>
     */
    public function sportsPhoto(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'sports_photo_upload_id');
    }

    /**
     * The single source of truth every consumer (live scoreboards) should
     * call rather than re-deriving the `sports_photo_upload_id === null`
     * check and `route()` call themselves — same shape as
     * `District::logoUrl()`.
     */
    public function sportsPhotoUrl(): ?string
    {
        return $this->sports_photo_upload_id === null ? null : route('athletes.sports-photo', $this);
    }

    public function fullName(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name, $this->name_extension])
            ->filter()
            ->join(' ');
    }

    public function age(): int
    {
        return (int) $this->birthdate->age;
    }

    /**
     * Grade-derived age division: grades 1–6 elementary, 7–12 secondary.
     */
    public function ageDivision(): AgeDivision
    {
        return $this->grade_level <= 6 ? AgeDivision::Elementary : AgeDivision::Secondary;
    }

    /**
     * One review per athlete (an athlete belongs to exactly one meet via
     * its delegation).
     *
     * @return HasOne<EligibilityReview, $this>
     */
    public function eligibilityReview(): HasOne
    {
        return $this->hasOne(EligibilityReview::class);
    }

    /**
     * @return HasMany<EligibilityDocument, $this>
     */
    public function eligibilityDocuments(): HasMany
    {
        return $this->hasMany(EligibilityDocument::class);
    }

    /**
     * @return HasOne<Accreditation, $this>
     */
    public function accreditation(): HasOne
    {
        return $this->hasOne(Accreditation::class);
    }

    /**
     * @return HasOne<MedicalClearance, $this>
     */
    public function medicalClearance(): HasOne
    {
        return $this->hasOne(MedicalClearance::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }
}
