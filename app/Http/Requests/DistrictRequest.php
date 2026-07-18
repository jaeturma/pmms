<?php

namespace App\Http\Requests;

use App\Models\District;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DistrictRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $district = $this->route('district');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('districts', 'name')
                    ->ignore($district instanceof District ? $district->id : null),
            ],
        ];
    }
}
