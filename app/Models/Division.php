<?php

namespace App\Models;

use App\Enums\DivisionType;
use Database\Factories\DivisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property DivisionType $type
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
