<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DelegationStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'school_id' => [
                'required',
                'integer',
                Rule::exists('schools', 'id')->where('active', true),
                Rule::unique('delegations', 'school_id')
                    ->where('meet_id', $this->integer('meet_id')),
            ],
            'head_name' => ['required', 'string', 'max:160'],
            'head_phone' => ['nullable', 'string', 'max:30'],
            'head_email' => ['nullable', 'email', 'max:160'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_id.unique' => __('This school already has a delegation for the selected meet.'),
        ];
    }
}
