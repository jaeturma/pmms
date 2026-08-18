<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\SportCategory;
use Illuminate\Database\Seeder;

class SportEventsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SportCategoriesSeeder::class);

        SportCategory::query()->with('sport')->whereNull('meet_sport_id')->get()->each(function (SportCategory $category): void {
            $names = $category->sport->code === 'ATHLETICS'
                ? ['100 Meter Dash', '200 Meter Dash', '400 Meter Dash', 'Long Jump', 'High Jump', 'Shot Put']
                : [$category->sport->name.' — '.$category->display_name];

            foreach ($names as $index => $name) {
                $rawCode = $category->sport->code.'_'.str($category->slug.'-'.$name)->slug('_')->upper();
                $code = strlen($rawCode) <= 80
                    ? $rawCode
                    : substr($rawCode, 0, 71).'_'.substr(sha1($rawCode), 0, 8);

                Event::query()->updateOrCreate(
                    [
                        'sport_id' => $category->sport_id, 'sport_category_id' => $category->id,
                        'name' => $name, 'gender' => $category->sex?->value ?? 'mixed',
                        'age_division' => $category->level?->value ?? 'secondary',
                    ],
                    [
                        'code' => $code,
                        'slug' => str($name)->slug(), 'event_type' => $category->sport->is_team_sport ? 'team' : 'individual',
                        'discipline' => $category->discipline, 'is_team_event' => $category->sport->is_team_sport,
                        'is_medal_event' => true, 'display_order' => $index + 1,
                        'max_entries_per_delegation' => 1, 'active' => true,
                    ],
                );
            }
        });
    }
}
