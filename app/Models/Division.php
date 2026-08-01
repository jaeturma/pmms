<?php

namespace App\Models;

use App\Enums\DivisionType;
use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property DivisionType $type
 * @property string $name
 * @property int|null $logo_upload_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read FileUpload|null $logo
 */
#[Fillable(['type', 'name'])]
class Division extends Model
{
    /** @use HasFactory<DivisionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DivisionType::class,
        ];
    }

    /**
     * The division's site-wide brand logo, shown in the public portal
     * header in place of the "PM" placeholder mark. `logo_upload_id` is
     * kept out of Fillable — only DivisionController sets it, after a
     * successful upload.
     *
     * @return BelongsTo<FileUpload, $this>
     */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'logo_upload_id');
    }

    /**
     * The single division settings row, created with a Province default on
     * first access so the app always has a division configured.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'type' => DivisionType::Province,
            'name' => 'Provincial Meet Division',
        ]);
    }

    /**
     * The administrative-area term for this division's type ("District"
     * for City, "Municipality" for Province) — both are the same
     * underlying `District` model, this only changes the label.
     */
    public function areaLabel(): string
    {
        return $this->type->areaLabel();
    }

    /**
     * The type is foundational to how delegations register — once any
     * delegation exists, changing it would orphan or misclassify existing
     * registrations, so it is locked.
     */
    public function typeIsLocked(): bool
    {
        return Delegation::query()->exists();
    }
}
