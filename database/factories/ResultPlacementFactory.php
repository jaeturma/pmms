<?php

namespace Database\Factories;

use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\EventResult;
use App\Models\ResultPlacement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResultPlacement>
 */
class ResultPlacementFactory extends Factory
{
    /**
     * Define the model's default state: a confirmed entry of the result's
     * own meet and event, placed first.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_result_id' => EventResult::factory(),
            'entry_id' => function (array $attributes) {
                $result = EventResult::query()->findOrFail((int) $attributes['event_result_id']);

                $delegation = Delegation::factory()->approved()->create(['meet_id' => $result->meet_id]);
                $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

                return Entry::factory()->confirmed()->create([
                    'athlete_id' => $athlete->id,
                    'delegation_id' => $delegation->id,
                    'event_id' => $result->event_id,
                ])->id;
            },
            'rank' => 1,
            'mark' => null,
            'is_tie' => false,
        ];
    }
}
