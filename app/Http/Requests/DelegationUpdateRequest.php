<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DelegationUpdateRequest extends FormRequest
{
    /**
     * Only head-of-delegation contact details are editable; the meet and
     * school of a delegation never change after creation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'head_name' => ['required', 'string', 'max:160'],
            'head_phone' => ['nullable', 'string', 'max:30'],
            'head_email' => ['nullable', 'email', 'max:160'],
        ];
    }
}
