<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MatchRequest extends FormRequest
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
            'event_schedule_id' => ['nullable', 'integer', Rule::exists('event_schedules', 'id')],
            'round_label' => ['required', 'string', 'max:60'],
            'sequence' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
