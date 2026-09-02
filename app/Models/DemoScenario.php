<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['meet_id', 'sport_id', 'created_by', 'request_token', 'name', 'template'])]
class DemoScenario extends Model
{
    public function meet(): BelongsTo { return $this->belongsTo(Meet::class); }
    public function sport(): BelongsTo { return $this->belongsTo(Sport::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function events(): HasMany { return $this->hasMany(Event::class); }
    public function schedules(): HasMany { return $this->hasMany(EventSchedule::class); }
    public function matches(): HasMany { return $this->hasMany(EventMatch::class); }
    public function results(): HasMany { return $this->hasMany(EventResult::class); }
}
