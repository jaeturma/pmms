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
            MeetSportAssignmentRole::TournamentManager->value,
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
            'medal_config' => ['sometimes', 'array'],
            'medal_config.awards_medals' => ['required_with:medal_config', 'boolean'],
            'medal_config.award_type' => ['required_with:medal_config', Rule::in(['INDIVIDUAL', 'PAIR', 'TEAM', 'RELAY', 'GROUP'])],
            'medal_config.physical_quantity_mode' => ['required_with:medal_config', Rule::in(['FIXED', 'TEAM_MEMBER_COUNT'])],
            'medal_config.gold_physical_quantity' => ['nullable', 'required_if:medal_config.awards_medals,1', 'integer', 'min:0', 'max:1000'],
            'medal_config.silver_physical_quantity' => ['nullable', 'required_if:medal_config.awards_medals,1', 'integer', 'min:0', 'max:1000'],
            'medal_config.bronze_physical_quantity' => ['nullable', 'required_if:medal_config.awards_medals,1', 'integer', 'min:0', 'max:1000'],
            'medal_config.gold_tally_quantity' => ['nullable', 'required_if:medal_config.awards_medals,1', 'integer', 'min:0', 'max:1000'],
            'medal_config.silver_tally_quantity' => ['nullable', 'required_if:medal_config.awards_medals,1', 'integer', 'min:0', 'max:1000'],
            'medal_config.bronze_tally_quantity' => ['nullable', 'required_if:medal_config.awards_medals,1', 'integer', 'min:0', 'max:1000'],
            'medal_config.notes' => ['nullable', 'string', 'max:2000'],
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
        return $this->safe()->except(['venues', 'medal_config']);
    }

    public function medalConfigData(): ?array
    {
        return $this->has('medal_config') ? $this->validated('medal_config') : null;
    }
}
