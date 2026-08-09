<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Meet;
use App\Models\SportCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
