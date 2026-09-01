<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Enums\AgeDivision;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\School;
use App\Models\SchoolDistrict;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        if ($athlete instanceof Athlete) {
            return Gate::allows('update', $athlete);
        }

        $delegation = Delegation::find($this->integer('delegation_id'));

        return $delegation !== null && Gate::allows('create', [Athlete::class, $delegation]);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('age_division') && $this->filled('grade_level')) {
            $this->merge(['age_division' => $this->integer('grade_level') <= 6 ? 'elementary' : 'secondary']);
        }

        if ($this->route('athlete') !== null || $this->filled('event_id') || $this->user()?->role !== UserRole::Coach) {
            return;
        }

        $delegation = Delegation::find($this->integer('delegation_id'));
        if ($delegation === null) {
            return;
        }

        $eventIds = $this->user()->approvedCoachEventIdsForDelegation($delegation);
        if ($eventIds->count() === 1) {
            $event = Event::find($eventIds->first());
            if ($event !== null && ! $event->is_team_event) {
                $this->merge(['event_id' => $event->id]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Birthdate sanity: school athletes are between 5 and 25 years old.
     * The delegation and school are both fixed after creation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $athlete = $this->route('athlete');

        $rules = [
            'first_name' => ['required', 'string', 'max:80'],
            'middle_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'name_extension' => ['nullable', Rule::in(['None', 'Jr.', 'Sr.', 'II', 'III'])],
            'sex' => ['required', Rule::enum(Sex::class)],
            'birthdate' => [
                'required',
                'date',
                'before:'.now()->subYears(5)->toDateString(),
                'after:'.now()->subYears(25)->toDateString(),
            ],
            'lrn' => [
                'required',
                'digits:12',
                Rule::unique('athletes', 'lrn')
                    ->whereNull('deleted_at')
                    ->ignore($athlete instanceof Athlete ? $athlete->id : null),
            ],
            'grade_level' => ['required', 'integer', 'min:0', 'max:12'],
            'age_division' => ['required', Rule::enum(AgeDivision::class)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('pmms.athlete_photos.max_upload_kb')],
            'sports_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('pmms.athlete_photos.max_upload_kb')],
            'athlete_history' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'athlete_history_page_2' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'form_10' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'school_id_document' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'birth_certificate' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'report_card' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'parental_consent' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
            'medical_certificate' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];

        if ($athlete === null) {
            $rules['middle_name'] = ['required', 'string', 'max:80'];
            $rules['name_extension'] = ['required', Rule::in(['None', 'Jr.', 'Sr.', 'II', 'III'])];
            $rules['delegation_id'] = ['required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['required', 'integer', Rule::exists('schools', 'id')->where('active', true)];
            if ($this->user()?->role === UserRole::Coach) {
                $rules['district_id'] = ['sometimes', 'required', 'integer', Rule::exists('districts', 'id')->where('active', true)];
                $rules['school_district_id'] = ['sometimes', 'required', 'integer', Rule::exists('school_districts', 'id')->where('active', true)];
            }
            $rules['event_id'] = $this->user()?->role === UserRole::Coach
                ? ['nullable', 'integer', Rule::exists('events', 'id')]
                : ['nullable', 'integer', Rule::exists('events', 'id')];
        } elseif ($this->user()?->isAdmin() || $this->user()?->canManageProductionAccounts()
            || Gate::allows('update', $athlete)) {
            $rules['delegation_id'] = ['sometimes', 'required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['sometimes', 'required', 'integer', Rule::exists('schools', 'id')->where('active', true)];
            $rules['meet_sport_ids'] = ['sometimes', 'array'];
            $rules['meet_sport_ids.*'] = ['integer', 'distinct', Rule::exists('meet_sports', 'id')];
            $rules['event_ids'] = ['sometimes', 'array'];
            $rules['event_ids.*'] = ['integer', 'distinct', Rule::exists('events', 'id')];
            $rules['registered_by'] = ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')];
        }

        return $rules;
    }

    /**
     * The school must belong to the delegation: exactly its own school for
     * a school-rooted (City) delegation, or any school in its municipality
     * for a district-rooted (Province) delegation — an athlete can't be
     * attributed to a school outside where their delegation actually
     * registered.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->route('athlete') !== null) {
                return;
            }

            $delegation = Delegation::find($this->integer('delegation_id'));
            $school = School::find($this->integer('school_id'));

            if ($delegation === null || $school === null) {
                return;
            }

            if ($this->user()?->role === UserRole::Coach && $this->filled('district_id')) {
                $municipalityId = $this->integer('district_id');
                $schoolDistrict = SchoolDistrict::find($this->integer('school_district_id'));
                $delegationMunicipalityId = $delegation->district_id ?? $delegation->school?->district_id;
                if ($delegationMunicipalityId !== $municipalityId) {
                    $validator->errors()->add('district_id', __('The athlete municipality must match the coach’s approved delegation.'));
                }
                if (! $this->filled('school_district_id') || $schoolDistrict === null || $schoolDistrict->district_id !== $municipalityId) {
                    $validator->errors()->add('school_district_id', __('The selected school district must belong to the athlete’s municipality.'));
                }
            }

            $event = $this->filled('event_id') ? Event::find($this->integer('event_id')) : null;

            if ($this->user()?->role === UserRole::Coach && $event !== null
                && ! $this->user()->hasApprovedCoachScope($delegation, $event)) {
                $validator->errors()->add('event_id', __('You may only register athletes for your approved sport and event assignment.'));
            }

            if ($event !== null && ! $delegation->meet->events()->whereKey($event->id)->exists()) {
                $validator->errors()->add('event_id', __('That event is not part of the athlete\'s meet.'));
            }

            $sex = Sex::tryFrom((string) $this->input('sex'));
            if ($this->user()?->role === UserRole::Coach && $event === null && $sex !== null) {
                $level = $this->integer('grade_level') <= 6 ? 'elementary' : 'secondary';
                $gender = $sex === Sex::Male ? ['boys', 'mixed'] : ['girls', 'mixed'];
                $matchingIndividualEvents = Event::query()
                    ->whereIn('id', $this->user()->approvedCoachEventIdsForDelegation($delegation))
                    ->whereIn('age_division', [$level, AgeDivision::ElementaryAndSecondary->value])
                    ->whereIn('gender', $gender)
                    ->where('is_team_event', false)
                    ->count();
                $hasMatchingScope = $matchingIndividualEvents > 0 || Event::query()
                    ->whereIn('id', $this->user()->approvedCoachEventIdsForDelegation($delegation))
                    ->whereIn('age_division', [$level, AgeDivision::ElementaryAndSecondary->value])
                    ->whereIn('gender', $gender)
                    ->exists();
                if (! $hasMatchingScope) {
                    $validator->errors()->add('event_id', __('Your active Coach assignment does not cover this athlete’s level and Event requirements.'));
                } elseif ($matchingIndividualEvents > 1) {
                    $validator->errors()->add('event_id', __('Select one of your approved sports and events for this athlete.'));
                }
            }
            if ($event !== null && $sex !== null && ! $event->gender->accepts($sex)) {
                $validator->errors()->add('event_id', __('The athlete\'s sex does not match this Event.'));
            }

            $grade = $this->integer('grade_level');
            $selectedDivision = AgeDivision::tryFrom((string) $this->input('age_division'));
            if ($event !== null && $selectedDivision !== null && ! $event->age_division->accepts($selectedDivision)) {
                $validator->errors()->add('event_id', __('The athlete\'s grade level does not match this event\'s age division.'));
            }

        });
    }
}
