<?php

namespace App\Models;

use App\Enums\DavraaReportDesignation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One printed row of a DAVRAA report. References an existing person by id
 * (`athlete_id` for ATHLETE rows, `coach_user_id` for COACH / ASST. COACH
 * / CHAPERONE) and stores a snapshot of the fields the template needs, so
 * a saved report is a stable document.
 */
#[Fillable([
    'davraa_report_group_id', 'designation', 'athlete_id', 'coach_user_id', 'sort_order',
    'last_name', 'given_names', 'middle_initial', 'lrn', 'school_name', 'district_name',
])]
class DavraaReportMember extends Model
{
    protected function casts(): array
    {
        return [
            'designation' => DavraaReportDesignation::class,
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(DavraaReportGroup::class, 'davraa_report_group_id');
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class)->withTrashed();
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coach_user_id')->withTrashed();
    }

    /** Which snapshot fields the template still needs filling in. */
    public function missingFields(): array
    {
        $required = $this->designation === DavraaReportDesignation::Athlete
            ? ['last_name', 'given_names', 'lrn', 'school_name', 'district_name']
            : ['last_name', 'given_names'];

        return array_values(array_filter(
            $required,
            fn (string $field): bool => blank($this->{$field}),
        ));
    }
}
