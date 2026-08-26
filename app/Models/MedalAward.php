<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_result_id', 'result_placement_id', 'delegation_id', 'school_id', 'rank', 'medal_type',
    'physical_quantity', 'tally_quantity', 'result_version', 'snapshotted_by', 'snapshotted_at',
])]
class MedalAward extends Model
{
    protected function casts(): array
    {
        return ['rank' => 'integer', 'physical_quantity' => 'integer', 'tally_quantity' => 'integer', 'snapshotted_at' => 'datetime'];
    }

    public function result(): BelongsTo { return $this->belongsTo(EventResult::class, 'event_result_id'); }
    public function placement(): BelongsTo { return $this->belongsTo(ResultPlacement::class, 'result_placement_id'); }
    public function delegation(): BelongsTo { return $this->belongsTo(Delegation::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
}
