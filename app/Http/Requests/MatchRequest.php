<?php

namespace App\Http\Requests;

use App\Models\Meet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MatchRequest extends FormRequest
{
    /**
     * A match always belongs to `Meet::current()` — this deployment runs
     * one meet, so nobody picks it.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['meet_id' => Meet::current()->id]);
    }

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
            'competition_area' => ['nullable', 'string', 'max:100'],
            'live_scoring_enabled' => ['sometimes', 'boolean'],
            'awards_medals' => ['sometimes', 'boolean'],
        ];
    }
}
