<?php

namespace App\Http\Requests;

use App\Services\DataIntegrityAccess;
use Illuminate\Validation\Rule;

/** Reuses canonical Athlete validation without invoking entry creation. */
class IntegrityAthleteRequest extends AthleteRequest
{
    public function authorize(): bool
    {
        $roster = $this->route('roster');

        return $roster?->meetSport !== null && app(DataIntegrityAccess::class)
            ->allows($this->user(), $roster->meetSport->meet_id, $roster->meetSport->sport_id);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['delegation_id' => $this->route('roster')?->delegation_id]);
        parent::prepareForValidation();
    }

    public function rules(): array
    {
        $rules = collect(parent::rules())->only([
            'first_name', 'middle_name', 'last_name', 'name_extension', 'sex', 'birthdate',
            'lrn', 'grade_level', 'age_division', 'school_id', 'delegation_id',
        ])->all();
        // An archived matching identity must be reviewed/restored, never silently duplicated.
        $rules['lrn'] = ['required', 'digits:12', Rule::unique('athletes', 'lrn')];

        return [...$rules,
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'expected_athlete_id' => ['required', 'integer'],
            'confirm_create' => ['required', 'accepted'],
            'event_id' => ['prohibited'], 'event_ids' => ['prohibited'],
        ];
    }
}
