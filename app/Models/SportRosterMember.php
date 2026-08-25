<?php

namespace App\Models;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meet_sport_id', 'delegation_id', 'athlete_id', 'level', 'gender'])]
class SportRosterMember extends Model
{
    protected function casts(): array
    {
        return ['level' => AgeDivision::class, 'gender' => GenderCategory::class];
    }

    public function meetSport(): BelongsTo
    {
        return $this->belongsTo(MeetSport::class);
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }
}
