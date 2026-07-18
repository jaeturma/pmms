<?php

namespace App\Http\Requests;

use App\Enums\SchoolLevel;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $school = $this->route('school');
        $schoolId = $school instanceof School ? $school->id : null;

        return [
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('schools', 'name')
                    ->where('district_id', $this->integer('district_id'))
                    ->ignore($schoolId),
            ],
            'school_id_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('schools', 'school_id_code')->ignore($schoolId),
            ],
            'level' => ['required', Rule::enum(SchoolLevel::class)],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
