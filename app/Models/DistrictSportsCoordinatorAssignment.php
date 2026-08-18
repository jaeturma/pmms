<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['meet_id', 'district_id', 'school_district_id', 'person_id', 'is_lead', 'status'])]
class DistrictSportsCoordinatorAssignment extends Model
{
    protected function casts(): array
    {
        return ['is_lead' => 'boolean'];
    }

    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function schoolDistrict(): BelongsTo
    {
        return $this->belongsTo(SchoolDistrict::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
