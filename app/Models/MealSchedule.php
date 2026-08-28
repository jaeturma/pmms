<?php

namespace App\Models;

use App\Enums\MealType;
use Database\Factories\MealScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $meet_id
 * @property MealType $meal_type
 * @property Carbon $date
 * @property string|null $starts_at
 * @property string|null $ends_at
 * @property int|null $venue_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['meet_id', 'meal_type', 'date', 'starts_at', 'ends_at', 'enforce_serving_time', 'venue_id', 'notes'])]
class MealSchedule extends Model
{
    /** @use HasFactory<MealScheduleFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meal_type' => MealType::class,
            'date' => 'date',
            'enforce_serving_time' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Meet, $this>
     */
    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    /**
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
