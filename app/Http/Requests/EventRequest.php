<?php

namespace App\Http\Requests;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Enums\MeetSportAssignmentRole;
use App\Models\Event;
use App\Models\Meet;
use App\Services\CompetitionAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isAdmin() || app(CompetitionAccessService::class)->hasAssignmentRole($user, [
            MeetSportAssignmentRole::TournamentSecretary->value,
            MeetSportAssignmentRole::TournamentICT->value,
        ], Meet::current()->id));
    }

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
            'venues' => ['sometimes', 'array'],
            'venues.*.venue_id' => ['required', 'integer', 'distinct', Rule::exists('venues', 'id')->where('active', true)],
            'venues.*.playing_area_type' => ['required', Rule::in(['venue', 'court', 'table'])],
            'venues.*.playing_area_count' => ['required', 'integer', 'min:1', 'max:100'],
            'venues.*.coordinator_ids' => ['sometimes', 'array', 'max:2'],
            'venues.*.coordinator_ids.*' => ['required', 'integer', 'distinct', Rule::exists('people', 'id')],
        ];
    }

    public function eventData(): array
    {
        return $this->safe()->except('venues');
    }
}
