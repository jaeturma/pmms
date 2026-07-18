<?php

namespace App\Http\Requests;

use App\Enums\PersonnelRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PersonnelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The delegation is fixed after creation.
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
        }

        return $rules;
    }
}
