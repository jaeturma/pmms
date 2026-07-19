<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventSchedule>
 */
class EventScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meet_id' => Meet::factory()->active(),
            'event_id' => Event::factory(),
            'venue_id' => Venue::factory(),
            'scheduled_date' => now()->addMonth()->toDateString(),
            'starts_at' => '08:00:00',
            'ends_at' => '10:00:00',
            'note' => null,
        ];
    }

    /**
     * Keep factory data valid: the scheduled event must belong to the meet.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (EventSchedule $schedule) {
            $schedule->meet->events()->syncWithoutDetaching([$schedule->event_id]);
        });
    }
}
