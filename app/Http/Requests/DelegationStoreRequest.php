<?php

namespace App\Http\Requests;

use App\Enums\DivisionType;
use App\Models\Division;
use App\Models\Meet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DelegationStoreRequest extends FormRequest
{
    /**
     * A delegation always registers under `Meet::current()` — this
     * deployment runs one meet, so nobody picks it.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['meet_id' => Meet::current()->id]);
    }

    /**
     * A delegation registers under a School (City division) or a District
     * (Province division, "Municipality") — whichever matches the current
     * division type; the other field is prohibited, not merely optional,
     * so a Province deployment can never accidentally create a
     * school-rooted delegation. See docs/division.md.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $isProvince = Division::current()->type === DivisionType::Province;

        return [
            'meet_id' => ['required', 'integer', Rule::exists('meets', 'id')],
            'school_id' => $isProvince
                ? ['prohibited']
                : [
                    'required',
                    'integer',
                    Rule::exists('schools', 'id')->where('active', true),
                    Rule::unique('delegations', 'school_id')
                        ->where('meet_id', $this->integer('meet_id')),
                ],
            'district_id' => $isProvince
                ? [
                    'required',
                    'integer',
                    Rule::exists('districts', 'id')->where('active', true),
                    Rule::unique('delegations', 'district_id')
                        ->where('meet_id', $this->integer('meet_id')),
                ]
                : ['prohibited'],
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
        $areaLabel = Division::current()->areaLabel();

        return [
            'school_id.unique' => __('This school already has a delegation for the selected meet.'),
            'district_id.unique' => __(':area already has a delegation for the selected meet.', ['area' => $areaLabel]),
        ];
    }
}
