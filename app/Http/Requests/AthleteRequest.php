<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Event;
use App\Models\School;
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
        if ($this->route('athlete') !== null || $this->filled('event_id') || $this->user()?->role !== UserRole::Coach) {
            return;
        }

        $eventId = $this->user()->coachAssignmentRequests()
            ->where('status', 'approved')
            ->where('delegation_id', $this->integer('delegation_id'))
            ->value('event_id');

        if ($eventId !== null) {
            $this->merge(['event_id' => $eventId]);
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
            'name_extension' => ['nullable', Rule::in(['Jr.', 'Sr.', 'II', 'III'])],
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
                    ->ignore($athlete instanceof Athlete ? $athlete->id : null),
            ],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'sports_photo' => ['nullable', 'image', 'max:5120'],
            'school_id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'birth_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'report_card' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];

        if ($athlete === null) {
            $rules['delegation_id'] = ['required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['required', 'integer', Rule::exists('schools', 'id')->where('active', true)];
            $rules['event_id'] = $this->user()?->role === UserRole::Coach
                ? ['required', 'integer', Rule::exists('events', 'id')]
                : ['nullable', 'integer', Rule::exists('events', 'id')];
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

            $event = $this->filled('event_id') ? Event::find($this->integer('event_id')) : null;

            if ($this->user()?->role === UserRole::Coach && $event !== null
                && ! $this->user()->hasApprovedCoachScope($delegation, $event)) {
                $validator->errors()->add('event_id', __('You may only register athletes for your approved sport and event assignment.'));
            }

            if ($event !== null && ! $delegation->meet->events()->whereKey($event->id)->exists()) {
                $validator->errors()->add('event_id', __('That event is not part of the athlete\'s meet.'));
            }

            $sex = Sex::tryFrom((string) $this->input('sex'));
            if ($event !== null && $sex !== null && ! $event->gender->accepts($sex)) {
                $validator->errors()->add('event_id', __('The athlete\'s sex does not match this event\'s gender category.'));
            }

            $grade = $this->integer('grade_level');
            if ($event !== null && $grade > 0 && $event->age_division->value !== ($grade <= 6 ? 'elementary' : 'secondary')) {
                $validator->errors()->add('event_id', __('The athlete\'s grade level does not match this event\'s age division.'));
            }

            if ($delegation->school_id !== null && $delegation->school_id !== $school->id) {
                $validator->errors()->add('school_id', __('The school must be the delegation\'s own school.'));
            } elseif ($delegation->district_id !== null && $delegation->district_id !== $school->district_id) {
                $validator->errors()->add('school_id', __('The school must belong to the delegation\'s municipality.'));
            }
        });
    }
}
