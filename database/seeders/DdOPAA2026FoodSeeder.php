<?php

namespace Database\Seeders;

use App\Enums\MealType;
use App\Models\MealSchedule;
use App\Models\Meet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DdOPAA2026FoodSeeder extends Seeder
{
    public function run(): void
    {
        $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();

        DB::transaction(function () use ($meet): void {
            foreach ($this->schedule() as [$date, $mealType, $startsAt, $endsAt]) {
                $meal = MealSchedule::query()
                    ->where('meet_id', $meet->id)
                    ->where('meal_type', $mealType->value)
                    ->whereDate('date', $date)
                    ->where('starts_at', $startsAt)
                    ->first();

                if ($meal === null) {
                    $meal = new MealSchedule([
                        'meet_id' => $meet->id,
                        'meal_type' => $mealType->value,
                        'date' => $date,
                        'starts_at' => $startsAt,
                    ]);
                }

                $meal->fill([
                    'ends_at' => $endsAt,
                    'enforce_serving_time' => true,
                    'notes' => 'DdOPAA Meet 2026 official meal schedule',
                ])->save();
            }
        });
    }

    /** @return array<int, array{string, MealType, string, string}> */
    private function schedule(): array
    {
        $rows = [
            ['2026-09-03', MealType::Lunch, '11:00', '14:00'],
            ['2026-09-03', MealType::Snack, '14:30', '15:30'],
            ['2026-09-03', MealType::Dinner, '17:00', '20:00'],
        ];

        foreach (range(4, 7) as $day) {
            $date = sprintf('2026-09-%02d', $day);
            array_push($rows,
                [$date, MealType::Breakfast, '05:00', '08:00'],
                [$date, MealType::Snack, '09:00', '10:00'],
                [$date, MealType::Lunch, '11:00', '14:00'],
                [$date, MealType::Snack, '14:30', '15:30'],
                [$date, MealType::Dinner, '17:00', '19:00'],
            );
        }

        array_push($rows,
            ['2026-09-08', MealType::Breakfast, '05:00', '08:00'],
            ['2026-09-08', MealType::Snack, '09:00', '10:00'],
            ['2026-09-08', MealType::Lunch, '11:00', '14:00'],
        );

        return $rows;
    }
}
