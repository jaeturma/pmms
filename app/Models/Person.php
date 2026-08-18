<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['source_key', 'full_name', 'normalized_name', 'source_flags'])]
class Person extends Model
{
    protected function casts(): array
    {
        return ['source_flags' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meetSportAssignments(): HasMany
    {
        return $this->hasMany(MeetSportAssignment::class);
    }

    public function accountProvision(): HasOne
    {
        return $this->hasOne(AccountProvision::class);
    }
}
