<?php

namespace App\Models;

use App\Enums\Sex;
use Database\Factories\AthleteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $delegation_id
 * @property string $first_name
 * @property string $last_name
 * @property Sex $sex
 * @property Carbon $birthdate
 * @property string $lrn
 * @property int $grade_level
 * @property int|null $photo_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['delegation_id', 'first_name', 'last_name', 'sex', 'birthdate', 'lrn', 'grade_level'])]
class Athlete extends Model
{
    /** @use HasFactory<AthleteFactory> */
    use HasFactory;

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
     * @return BelongsTo<FileUpload, $this>
     */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'photo_upload_id');
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function age(): int
    {
        return (int) $this->birthdate->age;
    }
}
