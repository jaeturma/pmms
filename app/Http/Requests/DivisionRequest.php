<?php

namespace App\Http\Requests;

use App\Enums\DivisionType;
use App\Models\Division;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DivisionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:160'],
        ];

        if (! Division::current()->typeIsLocked()) {
            $rules['type'] = ['required', Rule::enum(DivisionType::class)];
        }

        return $rules;
    }
}
