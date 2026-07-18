<?php

namespace App\Http\Requests;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $event = $this->route('event');

        return [
            'sport_id' => ['required', 'integer', Rule::exists('sports', 'id')],
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('events', 'name')
                    ->where('sport_id', $this->integer('sport_id'))
                    ->where('gender', $this->string('gender')->value())
                    ->where('age_division', $this->string('age_division')->value())
                    ->ignore($event instanceof Event ? $event->id : null),
            ],
            'gender' => ['required', Rule::enum(GenderCategory::class)],
            'age_division' => ['required', Rule::enum(AgeDivision::class)],
            'is_team_event' => ['required', 'boolean'],
            'max_entries_per_delegation' => ['required', 'integer', 'min:1', 'max:50'],
        ];
    }
}
