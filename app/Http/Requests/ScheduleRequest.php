<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleRequest extends FormRequest
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
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'venue_id' => [
                'required',
                'integer',
                Rule::exists('venues', 'id')->where('active', true),
            ],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'note' => ['nullable', 'string', 'max:255'],
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
            'venue_id.exists' => __('The selected venue is unavailable or archived.'),
            'ends_at.after' => __('The end time must be after the start time.'),
        ];
    }

    /**
     * Slot data with times normalized to H:i:s, matching column storage so
     * string comparisons stay correct on every database driver.
     *
     * @return array<string, mixed>
     */
    public function slotData(): array
    {
        $data = $this->validated();
        $data['starts_at'] .= ':00';
        $data['ends_at'] .= ':00';

        return $data;
    }
}
