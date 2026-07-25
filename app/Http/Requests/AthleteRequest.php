<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AthleteRequest extends FormRequest
{
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
            'last_name' => ['required', 'string', 'max:80'],
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
        ];

        if ($athlete === null) {
            $rules['delegation_id'] = ['required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['required', 'integer', Rule::exists('schools', 'id')->where('active', true)];
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

            if ($delegation->school_id !== null && $delegation->school_id !== $school->id) {
                $validator->errors()->add('school_id', __('The school must be the delegation\'s own school.'));
            } elseif ($delegation->district_id !== null && $delegation->district_id !== $school->district_id) {
                $validator->errors()->add('school_id', __('The school must belong to the delegation\'s municipality.'));
            }
        });
    }
}
