<?php

namespace App\Models;

use App\Enums\EntryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['delegation_id', 'event_id', 'status'])]
class TeamEntry extends Model
{
    protected function casts(): array
    {
        return ['status' => EntryStatus::class];
    }

    public function delegation(): BelongsTo
    {
        return $this->belongsTo(Delegation::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamEntryMember::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(ResultPlacement::class);
    }

    public function isRosterLocked(): bool
    {
        return $this->status === EntryStatus::Confirmed || $this->placements()->exists();
    }
}
