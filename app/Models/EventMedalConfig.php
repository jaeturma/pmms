<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'event_id', 'awards_medals', 'award_type', 'physical_quantity_mode',
    'gold_physical_quantity', 'silver_physical_quantity', 'bronze_physical_quantity',
    'gold_tally_quantity', 'silver_tally_quantity', 'bronze_tally_quantity', 'notes',
])]
class EventMedalConfig extends Model
{
    protected function casts(): array
    {
        return ['awards_medals' => 'boolean'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function isComplete(): bool
    {
        return ! $this->awards_medals || collect([
            $this->gold_physical_quantity, $this->silver_physical_quantity, $this->bronze_physical_quantity,
            $this->gold_tally_quantity, $this->silver_tally_quantity, $this->bronze_tally_quantity,
        ])->every(fn ($quantity): bool => $quantity !== null);
    }

    public function physicalQuantityForRank(int $rank): int
    {
        return (int) $this->{match ($rank) { 1 => 'gold_physical_quantity', 2 => 'silver_physical_quantity', default => 'bronze_physical_quantity' }};
    }

    public function tallyQuantityForRank(int $rank): int
    {
        return (int) $this->{match ($rank) { 1 => 'gold_tally_quantity', 2 => 'silver_tally_quantity', default => 'bronze_tally_quantity' }};
    }
}
