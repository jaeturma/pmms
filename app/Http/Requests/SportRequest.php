<?php

namespace App\Http\Requests;

use App\Models\Sport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $sport = $this->route('sport');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('sports', 'name')
                    ->ignore($sport instanceof Sport ? $sport->id : null),
            ],
        ];
    }
}
