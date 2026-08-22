<?php

namespace Database\Seeders;

use App\Models\CompetitionArea;
use App\Models\Event;
use App\Models\EventSchedule;
use App\Models\Meet;
use App\Models\Sport;
use App\Models\Venue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BasketballSecondaryBoysScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $meet = Meet::query()
                ->whereIn('name', ['DdOPAA Provincial Meet 2026', 'DdOPAA Meet 2026'])
                ->orderByRaw("name = 'DdOPAA Provincial Meet 2026' desc")
                ->first();

            if ($meet === null) {
                throw new RuntimeException('The DdOPAA Provincial Meet 2026 record was not found.');
            }

            $basketball = Sport::query()->where('name', 'Basketball')->where('active', true)->first();

            if ($basketball === null) {
                throw new RuntimeException('The active Basketball sport was not found.');
            }

            $event = Event::query()->firstOrCreate(
                [
                    'sport_id' => $basketball->id,
                    'name' => 'Basketball',
                    'age_division' => 'secondary',
                    'gender' => 'boys',
                ],
                [
                    'is_team_event' => true,
                    'max_entries_per_delegation' => 12,
                ],
            );

            $venue = Venue::query()->where('name', 'Municipal Gym')->where('active', true)->first();

            if ($venue === null) {
                throw new RuntimeException('The active Municipal Gym venue was not found.');
            }

            $competitionAreaId = CompetitionArea::query()
                ->where('venue_id', $venue->id)
                ->where('status', '!=', 'unavailable')
                ->orderBy('display_order')
                ->value('id');

            $days = [
                ['date' => '2026-09-05', 'start' => '07:00', 'end' => '18:00', 'games' => 7],
                ['date' => '2026-09-06', 'start' => '07:00', 'end' => '18:00', 'games' => 7],
                ['date' => '2026-09-07', 'start' => '07:00', 'end' => '18:00', 'games' => 5],
                ['date' => '2026-09-08', 'start' => '08:00', 'end' => '11:00', 'games' => 1],
            ];

            $gameNumber = 1;

            foreach ($days as $day) {
                $windowStart = CarbonImmutable::parse($day['date'].' '.$day['start']);
                $windowEnd = CarbonImmutable::parse($day['date'].' '.$day['end']);
                $windowMinutes = $windowStart->diffInMinutes($windowEnd);

                for ($index = 0; $index < $day['games']; $index++) {
                    $startsAt = $windowStart->addMinutes((int) round($windowMinutes * $index / $day['games']));
                    $endsAt = $windowStart->addMinutes((int) round($windowMinutes * ($index + 1) / $day['games']));
                    $note = sprintf('Game %d of 20 — Basketball Secondary Boys', $gameNumber);

                    EventSchedule::query()->updateOrCreate(
                        [
                            'meet_id' => $meet->id,
                            'event_id' => $event->id,
                            'note' => $note,
                        ],
                        [
                            'sport_category_id' => $event->sport_category_id,
                            'venue_id' => $venue->id,
                            'competition_area_id' => $competitionAreaId,
                            'scheduled_date' => $day['date'],
                            'starts_at' => $startsAt->format('H:i:s'),
                            'ends_at' => $endsAt->format('H:i:s'),
                        ],
                    );

                    $gameNumber++;
                }
            }

            $meet->events()->syncWithoutDetaching([$event->id]);
        });

        $this->command?->info('Basketball Secondary Boys: 20 Municipal Gym schedule slots encoded.');
    }
}
