<?php

namespace App\Models;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meet_sport_id', 'level', 'gender', 'max_athletes'])]
class SportRosterLimit extends Model
{
    protected function casts(): array
    {
        return ['level' => AgeDivision::class, 'gender' => GenderCategory::class, 'max_athletes' => 'integer'];
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }
}
