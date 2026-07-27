<?php

namespace App\Http\Requests;

use App\Models\SchoolDistrict;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolDistrictRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $schoolDistrict = $this->route('schoolDistrict');
        $schoolDistrictId = $schoolDistrict instanceof SchoolDistrict ? $schoolDistrict->id : null;

        return [
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('school_districts', 'name')
                    ->where('district_id', $this->integer('district_id'))
                    ->ignore($schoolDistrictId),
            ],
            'nickname' => ['nullable', 'string', 'max:60'],
        ];
    }
}
