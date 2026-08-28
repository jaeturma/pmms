<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meal_schedule_id', 'user_id', 'status', 'consumed_at', 'consumed_by_user_id', 'consumption_method', 'consumption_notes'])]
class MealEntitlement extends Model
{
    protected function casts(): array
    {
        return ['consumed_at' => 'datetime'];
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MealSchedule::class, 'meal_schedule_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function consumedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consumed_by_user_id');
    }
}
