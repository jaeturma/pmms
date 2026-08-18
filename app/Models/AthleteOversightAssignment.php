<?php

namespace App\Models;

use App\Enums\AthleteOversightType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'meet_id', 'authority_type', 'district_id', 'school_district_id', 'active'])]
class AthleteOversightAssignment extends Model
{
    protected function casts(): array
    {
        return ['authority_type' => AthleteOversightType::class, 'active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function schoolDistrict(): BelongsTo
    {
        return $this->belongsTo(SchoolDistrict::class);
    }
}
