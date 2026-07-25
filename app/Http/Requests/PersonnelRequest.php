<?php

namespace App\Http\Requests;

use App\Enums\PersonnelRole;
use App\Models\Delegation;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PersonnelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The delegation and school are both fixed after creation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'role' => ['required', Rule::enum(PersonnelRole::class)],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:160'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];

        if ($this->route('personnel') === null) {
            $rules['delegation_id'] = ['required', 'integer', Rule::exists('delegations', 'id')];
            $rules['school_id'] = ['required', 'integer', Rule::exists('schools', 'id')->where('active', true)];
        }

        return $rules;
    }

    /**
     * Same delegation/school consistency rule as AthleteRequest — see
     * there for why.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->route('personnel') !== null) {
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
