<?php

namespace App\Http\Requests;

use App\Models\Meet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MeetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $meet = $this->route('meet');

        return [
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('meets', 'name')
                    ->ignore($meet instanceof Meet ? $meet->id : null),
            ],
            'school_year' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'venue' => ['nullable', 'string', 'max:255'],
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
            'school_year.regex' => __('The school year must look like 2025-2026.'),
        ];
    }
}
