<?php

namespace App\Models;

use App\Enums\AgeDivision;
use App\Enums\Sex;
use Database\Factories\AthleteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $delegation_id
 * @property int $school_id
 * @property int|null $registered_by
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
#[Fillable(['delegation_id', 'school_id', 'first_name', 'middle_name', 'last_name', 'name_extension', 'sex', 'birthdate', 'lrn', 'grade_level', 'age_division'])]
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
            'age_division' => AgeDivision::class,
        ];
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(get: fn (?string $value): ?string => $value === null ? null : mb_strtoupper($value));
    }

    protected function middleName(): Attribute
    {
        return Attribute::make(get: fn (?string $value): ?string => $value === null ? null : mb_strtoupper($value));
    }

    protected function lastName(): Attribute
    {
        return Attribute::make(get: fn (?string $value): ?string => $value === null ? null : mb_strtoupper($value));
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

    /** @return BelongsTo<User, $this> */
    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function isOwnedBy(User $user): bool
    {
        if ($this->registered_by !== null) {
            return $this->registered_by === $user->id;
        }

        return AuditLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'athlete.created')
            ->where('auditable_type', $this->getMorphClass())
            ->where('auditable_id', $this->id)
            ->exists();
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $athletes) => $athletes
            ->where('registered_by', $user->id)
            ->orWhereExists(fn ($logs) => $logs
                ->selectRaw('1')
                ->from('audit_logs')
                ->whereColumn('audit_logs.auditable_id', 'athletes.id')
                ->where('audit_logs.auditable_type', $this->getMorphClass())
                ->where('audit_logs.action', 'athlete.created')
                ->where('audit_logs.user_id', $user->id)));
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_upload_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_upload_id === null
            ? null
            : route('athletes.photo', $this).'?v='.$this->photo_upload_id;
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
        return $this->sports_photo_upload_id === null
            ? null
            : route('athletes.sports-photo', $this).'?v='.$this->sports_photo_upload_id;
    }

    public function fullName(): string
    {
        return mb_strtoupper(collect([$this->first_name, $this->middle_name, $this->last_name, $this->name_extension])
            ->filter(fn (?string $part): bool => filled($part) && ! in_array(strtolower(trim($part)), ['n/a', 'none'], true))
            ->join(' '));
    }

    public function age(): int
    {
        return (int) $this->birthdate->age;
    }

    public function gradeLevelLabel(): string
    {
        return $this->grade_level === 0 ? 'Non-Graded' : "Grade {$this->grade_level}";
    }

    /**
     * Grade-derived age division: grades 1–6 elementary, 7–12 secondary.
     */
    public function ageDivision(): AgeDivision
    {
        return $this->age_division ?? ($this->grade_level <= 6 ? AgeDivision::Elementary : AgeDivision::Secondary);
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

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamEntryMember::class);
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

    public function sportRosterMemberships(): HasMany
    {
        return $this->hasMany(SportRosterMember::class);
    }

    /** @return Collection<int, string> */
    public function rosterSportNames(): Collection
    {
        return $this->sportRosterMemberships
            ->pluck('meetSport.sport.name')
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }
}
