<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportRosterLimit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SwimmingCompetitionSeeder extends Seeder
{
    /** @var array<int, array{int, string, string, string}> */
    private const EVENTS = [
        [1, '200m Freestyle', 'elementary', 'boys'], [2, '200m Freestyle', 'elementary', 'girls'],
        [3, '400m Freestyle', 'secondary', 'boys'], [4, '400m Freestyle', 'secondary', 'girls'],
        [5, '50m Butterfly', 'elementary', 'boys'], [6, '50m Butterfly', 'elementary', 'girls'],
        [7, '50m Butterfly', 'secondary', 'boys'], [8, '50m Butterfly', 'secondary', 'girls'],
        [9, '100m Backstroke', 'elementary', 'boys'], [10, '100m Backstroke', 'elementary', 'girls'],
        [11, '100m Backstroke', 'secondary', 'boys'], [12, '100m Backstroke', 'secondary', 'girls'],
        [13, '200m Butterfly', 'secondary', 'boys'], [14, '200m Butterfly', 'secondary', 'girls'],
        [15, '4x50m Medley Relay', 'elementary', 'boys'], [16, '4x50m Medley Relay', 'elementary', 'girls'],
        [17, '4x50m Medley Relay', 'secondary', 'boys'], [18, '4x50m Medley Relay', 'secondary', 'girls'],
        [19, '200m Breaststroke', 'secondary', 'boys'], [20, '200m Breaststroke', 'secondary', 'girls'],
        [21, '200m Individual Medley', 'elementary', 'boys'], [22, '200m Individual Medley', 'elementary', 'girls'],
        [23, '200m Individual Medley', 'secondary', 'boys'], [24, '200m Individual Medley', 'secondary', 'girls'],
        [25, '50m Breaststroke', 'elementary', 'boys'], [26, '50m Breaststroke', 'elementary', 'girls'],
        [27, '50m Breaststroke', 'secondary', 'boys'], [28, '50m Breaststroke', 'secondary', 'girls'],
        [29, '100m Freestyle', 'elementary', 'boys'], [30, '100m Freestyle', 'elementary', 'girls'],
        [31, '100m Freestyle', 'secondary', 'boys'], [32, '100m Freestyle', 'secondary', 'girls'],
        [33, '4x100m Medley Relay', 'elementary', 'boys'], [34, '4x100m Medley Relay', 'elementary', 'girls'],
        [35, '4x100m Medley Relay', 'secondary', 'boys'], [36, '4x100m Medley Relay', 'secondary', 'girls'],
        [37, '400m Freestyle', 'elementary', 'boys'], [38, '400m Freestyle', 'elementary', 'girls'],
        [39, '1500m Freestyle', 'secondary', 'boys'], [40, '800m Freestyle', 'secondary', 'girls'],
        [41, '200m Backstroke', 'secondary', 'boys'], [42, '200m Backstroke', 'secondary', 'girls'],
        [43, '100m Butterfly', 'elementary', 'boys'], [44, '100m Butterfly', 'elementary', 'girls'],
        [45, '100m Butterfly', 'secondary', 'boys'], [46, '100m Butterfly', 'secondary', 'girls'],
        [47, '50m Backstroke', 'elementary', 'boys'], [48, '50m Backstroke', 'elementary', 'girls'],
        [49, '50m Backstroke', 'secondary', 'boys'], [50, '50m Backstroke', 'secondary', 'girls'],
        [51, '4x50m Freestyle Relay', 'elementary', 'boys'], [52, '4x50m Freestyle Relay', 'elementary', 'girls'],
        [53, '4x50m Freestyle Relay', 'secondary', 'boys'], [54, '4x50m Freestyle Relay', 'secondary', 'girls'],
        [55, '400m Individual Medley', 'secondary', 'boys'], [56, '400m Individual Medley', 'secondary', 'girls'],
        [57, '50m Freestyle', 'elementary', 'boys'], [58, '50m Freestyle', 'elementary', 'girls'],
        [59, '50m Freestyle', 'secondary', 'boys'], [60, '50m Freestyle', 'secondary', 'girls'],
        [61, '200m Freestyle', 'secondary', 'boys'], [62, '200m Freestyle', 'secondary', 'girls'],
        [63, '100m Breaststroke', 'elementary', 'boys'], [64, '100m Breaststroke', 'elementary', 'girls'],
        [65, '100m Breaststroke', 'secondary', 'boys'], [66, '100m Breaststroke', 'secondary', 'girls'],
        [67, '4x100m Freestyle Relay', 'elementary', 'boys'], [68, '4x100m Freestyle Relay', 'elementary', 'girls'],
        [69, '4x100m Freestyle Relay', 'secondary', 'boys'], [70, '4x100m Freestyle Relay', 'secondary', 'girls'],
        [71, '800m Freestyle', 'secondary', 'boys'], [72, '1500m Freestyle', 'secondary', 'girls'],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();
            $sport = Sport::query()->where('code', 'SWIMMING')->firstOrFail();
            $meetSport = MeetSport::query()->whereBelongsTo($meet)->whereBelongsTo($sport)->firstOrFail();
            $categories = collect();

            foreach ([['elementary', 'boys', 11], ['elementary', 'girls', 11], ['secondary', 'boys', 12], ['secondary', 'girls', 12]] as $index => [$level, $gender, $limit]) {
                $label = ucfirst($level).' '.ucfirst($gender);
                $category = SportCategory::query()->updateOrCreate(
                    ['sport_id' => $sport->id, 'meet_sport_id' => null, 'slug' => Str::slug($label)],
                    ['name' => $label, 'display_name' => $label, 'level' => $level, 'sex' => $gender, 'classification' => 'regular', 'event_type' => 'mixed', 'competition_format' => 'timed', 'display_order' => $index + 1, 'active' => true],
                );
                $categories->put("{$level}:{$gender}", $category);
                SportRosterLimit::query()->updateOrCreate(
                    ['meet_sport_id' => $meetSport->id, 'level' => $level, 'gender' => $gender],
                    ['max_athletes' => $limit],
                );
            }

            foreach (self::EVENTS as [$number, $name, $level, $gender]) {
                $relay = str_contains($name, 'Relay');
                preg_match('/^(?:(\d+)x)?(\d+)m/', $name, $matches);
                $legs = $relay ? (int) $matches[1] : null;
                $legDistance = $relay ? (int) $matches[2] : null;
                $distance = $relay ? $legs * $legDistance : (int) $matches[2];
                $stroke = match (true) {
                    str_contains($name, 'Medley Relay') => 'MEDLEY_RELAY',
                    str_contains($name, 'Freestyle Relay') => 'FREESTYLE_RELAY',
                    str_contains($name, 'Individual Medley') => 'INDIVIDUAL_MEDLEY',
                    str_contains($name, 'Butterfly') => 'BUTTERFLY',
                    str_contains($name, 'Backstroke') => 'BACKSTROKE',
                    str_contains($name, 'Breaststroke') => 'BREASTSTROKE',
                    default => 'FREESTYLE',
                };
                $code = sprintf('SWIM-%s-%s-%s', $level === 'elementary' ? 'ELEM' : 'SEC', $gender === 'boys' ? 'B' : 'G', Str::upper(Str::replace(['M-', 'M '], ['-', '-'], Str::slug($name, '-'))));
                $event = Event::query()->updateOrCreate(
                    ['sport_id' => $sport->id, 'name' => $name, 'gender' => $gender, 'age_division' => $level],
                    ['sport_category_id' => $categories->get("{$level}:{$gender}")->id, 'code' => $code, 'event_no' => $number, 'slug' => Str::slug($name), 'event_type' => $relay ? 'RELAY' : 'INDIVIDUAL', 'discipline' => 'Swimming', 'distance' => $relay ? "{$legs}x{$legDistance}m" : "{$distance}m", 'distance_meters' => $distance, 'stroke' => $stroke, 'team_size' => $relay ? 4 : null, 'relay_legs' => $legs, 'relay_leg_distance_meters' => $legDistance, 'is_team_event' => $relay, 'is_medal_event' => true, 'display_order' => $number, 'max_entries_per_delegation' => 1, 'active' => true],
                );
                $meet->events()->syncWithoutDetaching([$event->id]);
            }
        });
    }
}
