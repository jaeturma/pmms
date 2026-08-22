<?php

namespace App\Http\Requests;

use App\Enums\SchoolLevel;
use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSchoolMasterData() ?? false;
    }

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
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')->where('active', true)],
            'school_district_id' => [
                'required',
                'integer',
                Rule::exists('school_districts', 'id')
                    ->where('district_id', $this->integer('district_id'))
                    ->where('active', true),
            ],
            'name' => [
                'required',
                'string',
                'max:160',
            ],
            'school_id_code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('schools', 'school_id_code')->ignore($schoolId),
            ],
            'school_type' => ['nullable', Rule::in(['Public', 'Private'])],
            'level' => ['nullable', Rule::enum(SchoolLevel::class)],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }
}
