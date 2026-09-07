<?php

namespace App\Models;

use App\Enums\DavraaReportLevel;
use App\Enums\DavraaReportStatus;
use App\Enums\GenderCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An ICT-defined DAVRAA reporting bundle: a user-named group of Sports
 * Events plus a hand-picked qualifier roster. The grouping is a
 * reporting decision, not derived from Match/Result/Medal/Schedule/Entry.
 */
#[Fillable(['meet_id', 'sport_id', 'name', 'division', 'level', 'status', 'notes', 'created_by'])]
class DavraaReportGroup extends Model
{
    protected function casts(): array
    {
        return [
            'division' => GenderCategory::class,
            'level' => DavraaReportLevel::class,
            'status' => DavraaReportStatus::class,
        ];
    }

    public function meet(): BelongsTo
    {
        return $this->belongsTo(Meet::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /** @return BelongsToMany<Event, $this> */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'davraa_report_group_event');
    }

    /** @return HasMany<DavraaReportMember, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(DavraaReportMember::class)->orderBy('sort_order')->orderBy('id');
    }

    public function isEditable(): bool
    {
        return $this->status !== DavraaReportStatus::Archived;
    }
}
