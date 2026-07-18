<?php

namespace App\Http\Requests;

use App\Enums\Sex;
use App\Models\Athlete;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AthleteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Birthdate sanity: school athletes are between 5 and 25 years old.
     * The delegation is fixed after creation.
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
        }

        return $rules;
    }
}
