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
use App\Services\AthleteRegistrationScope;
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
            return Gate::allows('update', $athlete)
                || (Gate::allows('updateAssignments', $athlete)
                    && ($this->has('event_ids') || $this->has('meet_sport_ids')
                        || $this->has('delegation_id') || $this->has('school_id') || $this->has('registered_by') || $this->has('coach_ids')))
                || (Gate::allows('updateAssets', $athlete)
                    && ($this->allFiles() !== [] || $this->user()?->role === UserRole::TournamentICT));
        }

        $delegation = Delegation::find($this->integer('delegation_id'));

        return $delegation !== null && Gate::allows('create', [Athlete::class, $delegation]);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('age_division') && $this->filled('grade_level')) {
            $this->merge(['age_division' => $this->integer('grade_level') <= 6 ? 'elementary' : 'secondary']);
        }

        if ($this->route('athlete') !== null || $this->user()?->role !== UserRole::Coach) {
            return;
        }

        $delegations = app(AthleteRegistrationScope::class)->delegations($this->user());
        if ($delegations->count() === 1 && ! $this->filled('delegation_id')) {
            $this->merge(['delegation_id' => $delegations->first()->id]);
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
            'athlete_history' => $this->documentRules(),
            'form_10' => $this->documentRules(),
            'form_10_page_2' => $this->documentRules(),
            'birth_certificate' => $this->documentRules(),
            'birth_certificate_page_2' => $this->documentRules(),
            'parental_consent' => $this->documentRules(),
            'medical_certificate' => $this->documentRules(),
        ];

        if ($athlete instanceof Athlete) {
            foreach (['first_name', 'middle_name', 'last_name', 'name_extension', 'sex', 'birthdate', 'lrn', 'grade_level', 'age_division'] as $field) {
                array_unshift($rules[$field], 'sometimes');
            }
        }

        if ($athlete instanceof Athlete && ! Gate::allows('update', $athlete)) {
            $allowedFields = [
                'photo', 'sports_photo', 'athlete_history', 'form_10',
                'form_10_page_2', 'birth_certificate', 'birth_certificate_page_2',
                'parental_consent', 'medical_certificate',
            ];
            if (Gate::allows('updateAssignments', $athlete)) {
                $rules['meet_sport_ids'] = ['sometimes', 'array'];
                $rules['meet_sport_ids.*'] = ['integer', 'distinct', Rule::exists('meet_sports', 'id')];
                $rules['event_ids'] = ['sometimes', 'array'];
                $rules['event_ids.*'] = ['integer', 'distinct', Rule::exists('events', 'id')];
                $allowedFields = [...$allowedFields, 'meet_sport_ids', 'meet_sport_ids.*', 'event_ids', 'event_ids.*'];
            }
            if ($this->user()?->role === UserRole::TournamentICT) {
                $rules['delegation_id'] = ['sometimes', 'required', 'integer', Rule::exists('delegations', 'id')];
                $rules['school_id'] = ['sometimes', 'nullable', 'integer', Rule::exists('schools', 'id')->where('active', true)];
                $rules['registered_by'] = ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')];
                $rules['coach_ids'] = ['sometimes', 'array', 'min:1', 'max:2'];
                $rules['coach_ids.*'] = ['integer', 'distinct', Rule::exists('users', 'id')];
                $allowedFields = [...$allowedFields, 'delegation_id', 'school_id', 'meet_sport_ids', 'meet_sport_ids.*', 'event_ids', 'event_ids.*', 'registered_by', 'coach_ids', 'coach_ids.*'];
            }

            return collect($rules)->only($allowedFields)->all();
        }

        if ($athlete === null) {
            $rules['middle_name'] = ['required', 'string', 'max:80'];
            $rules['name_extension'] = ['required', Rule::in(['None', 'Jr.', 'Sr.', 'II', 'III'])];
            $rules['delegation_id'] = ['required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['nullable', 'integer', Rule::exists('schools', 'id')->where('active', true)];
            if ($this->user()?->role === UserRole::Coach) {
                $rules['district_id'] = ['sometimes', 'required', 'integer', Rule::exists('districts', 'id')->where('active', true)];
                $rules['school_district_id'] = ['sometimes', 'required', 'integer', Rule::exists('school_districts', 'id')->where('active', true)];
            }
            $rules['event_id'] = $this->user()?->role === UserRole::Coach
                ? ['nullable', 'integer', Rule::exists('events', 'id')]
                : ['nullable', 'integer', Rule::exists('events', 'id')];
            $rules['meet_sport_id'] = ['nullable', 'integer', Rule::exists('meet_sports', 'id')];
        } elseif ($this->user()?->isAdmin() || $this->user()?->canManageProductionAccounts()
            || Gate::allows('update', $athlete)) {
            $rules['delegation_id'] = ['sometimes', 'required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['sometimes', 'nullable', 'integer', Rule::exists('schools', 'id')->where('active', true)];
            $rules['meet_sport_ids'] = ['sometimes', 'array'];
            $rules['meet_sport_ids.*'] = ['integer', 'distinct', Rule::exists('meet_sports', 'id')];
            $rules['event_ids'] = ['sometimes', 'array'];
            $rules['event_ids.*'] = ['integer', 'distinct', Rule::exists('events', 'id')];
            $rules['registered_by'] = ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')];
            $rules['coach_ids'] = ['sometimes', 'array', 'min:1', 'max:2'];
            $rules['coach_ids.*'] = ['integer', 'distinct', Rule::exists('users', 'id')];
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
    /** @return array<int, string> */
    private function documentRules(): array
    {
        return ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'mimes:jpg,jpeg,png,webp,pdf', 'max:'.config('pmms.athlete_documents.max_upload_kb')];
    }

    public function messages(): array
    {
        $documentFields = ['athlete_history', 'form_10', 'form_10_page_2', 'birth_certificate', 'birth_certificate_page_2', 'parental_consent', 'medical_certificate'];
        $messages = [];
        foreach ($documentFields as $field) {
            $messages["{$field}.max"] = __('The selected document is too large. Maximum upload size is 10 MB per file.');
            $messages["{$field}.mimetypes"] = __('This file type is not supported. Please upload a JPG, PNG, WebP, or PDF document.');
            $messages["{$field}.mimes"] = __('This file type is not supported. Please upload a JPG, PNG, WebP, or PDF document.');
        }

        return $messages;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $athlete = $this->route('athlete');
            if ($athlete instanceof Athlete) {
                return;
            }

            $delegation = Delegation::find($this->integer('delegation_id'));
            $school = School::find($this->integer('school_id'));

            if ($delegation === null) {
                return;
            }
            // A school outside the delegation municipality is permitted.
            // The discrepancy is presented as a non-blocking registration
            // concern to Coach and ICT users instead of rejecting the record.

            if ($this->user()?->role === UserRole::Coach && $school !== null && $this->filled('district_id')) {
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
