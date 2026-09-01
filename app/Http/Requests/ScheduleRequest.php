<?php

namespace App\Http\Requests;

use App\Models\CompetitionArea;
use App\Models\Event;
use App\Models\EventVenue;
use App\Models\Meet;
use App\Models\MeetSportVenue;
use App\Models\SportCategory;
use App\Models\SportCategoryCompetitionArea;
use App\Services\CompetitionAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ScheduleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $event = Event::query()->find($this->integer('event_id'));
        if ($event === null) {
            return;
        }

        $categoryId = $event->sport_category_id;
        $venueId = EventVenue::query()
            ->where('event_id', $event->id)
            ->orderBy('id')
            ->value('venue_id');

        if ($venueId === null && $categoryId !== null) {
            $venueId = SportCategoryCompetitionArea::query()
                ->where('sport_category_id', $categoryId)
                ->where('status', 'active')
                ->whereHas('meetSport', fn ($meetSports) => $meetSports
                    ->where('meet_id', Meet::current()->id)
                    ->where('sport_id', $event->sport_id)
                    ->where('active', true))
                ->orderBy('id')
                ->value('venue_id');
        }

        if ($venueId === null) {
            $venueId = MeetSportVenue::query()
                ->whereHas('meetSport', fn ($meetSports) => $meetSports
                    ->where('meet_id', Meet::current()->id)
                    ->where('sport_id', $event->sport_id)
                    ->where('active', true))
                ->orderBy('id')
                ->value('venue_id');
        }

        $derived = [];
        if ($categoryId !== null) {
            $derived['sport_category_id'] = $categoryId;
        }
        if ($venueId !== null) {
            $derived['venue_id'] = $venueId;
        }

        $this->merge($derived);
    }

    public function authorize(): bool
    {
        $user = $this->user();
        $access = app(CompetitionAccessService::class);

        return $user !== null && ($user->isAdmin()
            || $access->hasAssignmentRole($user, $access->competitionManagerRoles(), Meet::current()->id));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'event_id' => ['required', 'integer', Rule::exists('events', 'id')],
            'sport_category_id' => [
                'nullable',
                'integer',
                Rule::exists('sport_categories', 'id')->where('active', true),
            ],
            'venue_id' => [
                'required',
                'integer',
                Rule::exists('venues', 'id')->where('active', true),
            ],
            'competition_area_id' => [
                'nullable',
                'integer',
                Rule::exists('competition_areas', 'id')->where('status', '!=', 'unavailable'),
            ],
            'scheduled_date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * A chosen category must belong to the same sport as the scheduled
     * event — a category is additive context for that event's slot, never
     * a different sport's classification (see `EventSchedule::sportCategory()`'s
     * own docblock).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categoryId = $this->integer('sport_category_id');
            $eventId = $this->integer('event_id');
            $areaId = $this->integer('competition_area_id');
            $venueId = $this->integer('venue_id');

            if ($eventId > 0 && $venueId > 0) {
                $event = Event::query()->find($eventId);
                $meetSportId = $event === null ? null : MeetSportVenue::query()
                    ->whereHas('meetSport', fn ($meetSports) => $meetSports
                        ->where('meet_id', Meet::current()->id)
                        ->where('sport_id', $event->sport_id)
                        ->where('active', true))
                    ->value('meet_sport_id');
                $hasCategoryConfiguration = $meetSportId !== null && SportCategoryCompetitionArea::query()
                    ->where('meet_sport_id', $meetSportId)
                    ->where('status', 'active')
                    ->exists();

                if ($hasCategoryConfiguration) {
                    if ($categoryId <= 0) {
                        $validator->errors()->add('sport_category_id', __('Select a sport category before choosing its venue.'));
                    } elseif ($areaId <= 0) {
                        $validator->errors()->add('competition_area_id', __('Select an available competition area for this category.'));
                    } elseif (! SportCategoryCompetitionArea::query()
                        ->where('meet_sport_id', $meetSportId)
                        ->where('sport_category_id', $categoryId)
                        ->where('venue_id', $venueId)
                        ->where('competition_area_id', $areaId)
                        ->where('status', 'active')
                        ->exists()) {
                        $validator->errors()->add('venue_id', __('That venue or competition area is not available to the selected category.'));
                    }
                }

                $venueAssignments = EventVenue::query()->where('event_id', $eventId);

                // Events with configured venues must use one of them. Events
                // without assignments retain legacy behavior until configured.
                if (! $hasCategoryConfiguration && $venueAssignments->exists()) {
                    $assignment = (clone $venueAssignments)->where('venue_id', $venueId)->first();

                    if ($assignment === null) {
                        $validator->errors()->add('venue_id', __('That venue is not assigned to the selected event.'));
                    } elseif ($assignment->playing_area_count > 1 && $areaId <= 0) {
                        $validator->errors()->add(
                            'competition_area_id',
                            __('Select a :area for this event.', ['area' => $assignment->playing_area_type]),
                        );
                    } elseif ($areaId > 0 && ! CompetitionArea::query()
                        ->whereKey($areaId)
                        ->where('area_type', $assignment->playing_area_type)
                        ->exists()) {
                        $validator->errors()->add(
                            'competition_area_id',
                            __('That playing area is not a :area for the selected event.', ['area' => $assignment->playing_area_type]),
                        );
                    }
                } else {
                    $event = Event::query()->find($eventId);
                    $sportAssignment = $event === null ? null : MeetSportVenue::query()
                        ->where('venue_id', $venueId)
                        ->whereHas('meetSport', fn ($meetSports) => $meetSports
                            ->where('meet_id', Meet::current()->id)
                            ->where('sport_id', $event->sport_id)
                            ->where('active', true))
                        ->first();

                    $sportHasAssignedVenues = $event !== null && MeetSportVenue::query()
                        ->whereHas('meetSport', fn ($meetSports) => $meetSports
                            ->where('meet_id', Meet::current()->id)
                            ->where('sport_id', $event->sport_id)
                            ->where('active', true))
                        ->exists();

                    if ($sportHasAssignedVenues && $sportAssignment === null) {
                        $validator->errors()->add('venue_id', __('That venue is not assigned to the selected event\'s sport.'));
                    } elseif (($sportAssignment?->expected_area_count ?? 1) > 1 && $areaId <= 0) {
                        $validator->errors()->add('competition_area_id', __('Select a competition area for this venue.'));
                    }
                }
            }

            if ($areaId > 0 && $venueId > 0) {
                $areaMatchesVenue = CompetitionArea::query()
                    ->whereKey($areaId)
                    ->where('venue_id', $venueId)
                    ->exists();

                if (! $areaMatchesVenue) {
                    $validator->errors()->add(
                        'competition_area_id',
                        __('That competition area belongs to a different venue.'),
                    );
                }
            }

            if ($categoryId <= 0 || $eventId <= 0) {
                return;
            }

            $event = Event::query()->find($eventId);

            if ($event === null) {
                return;
            }

            $categoryBelongsToEventSport = SportCategory::query()
                ->whereKey($categoryId)
                ->where('sport_id', $event->sport_id)
                ->exists();

            if (! $categoryBelongsToEventSport) {
                $validator->errors()->add(
                    'sport_category_id',
                    __('That category belongs to a different sport than the selected event.'),
                );
            }
        });
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
     * Slot data with times normalized to H:i:s (matching column storage so
     * string comparisons stay correct on every database driver) and
     * `meet_id` filled in automatically — this deployment only ever
     * schedules against `Meet::current()`, so nobody picks a meet.
     *
     * @return array<string, mixed>
     */
    public function slotData(): array
    {
        $data = $this->validated();
        $data['starts_at'] .= ':00';
        $data['ends_at'] .= ':00';
        $data['meet_id'] = Meet::current()->id;

        return $data;
    }
}
