<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_entry_id', 'athlete_id', 'entry_id', 'member_order'])]
class TeamEntryMember extends Model
{
    protected function casts(): array
    {
        return ['member_order' => 'integer'];
    }
    public function teamEntry(): BelongsTo
    {
        return $this->belongsTo(TeamEntry::class);
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(Entry::class);
    }
}
