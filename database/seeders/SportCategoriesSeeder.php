<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Database\Seeder;

class SportCategoriesSeeder extends Seeder
{
    /** Levels confirmed by the supplied brief or existing PMMS configuration. */
    private const array LEVELS = [
        'ARCHERY' => ['secondary'], 'BASKETBALL' => ['elementary', 'secondary'],
        'BASKETBALL_3X3' => ['secondary'], 'BASEBALL' => ['secondary'],
        'BOXING' => ['secondary'], 'FOOTBALL' => ['secondary'], 'SOFTBALL' => ['secondary'],
        'TENNIS' => ['secondary'], 'VOLLEYBALL' => ['elementary', 'secondary'], 'WEIGHTLIFTING' => ['secondary'],
        'WRESTLING' => ['secondary'], 'PENCAK_SILAT' => ['secondary'], 'SEPAK_TAKRAW' => ['secondary'],
        'ATHLETICS' => ['elementary', 'secondary'], 'ARNIS' => ['elementary', 'secondary'],
        'BADMINTON' => ['elementary', 'secondary'], 'CHESS' => ['elementary', 'secondary'],
        'DANCESPORT' => ['elementary', 'secondary'], 'FUTSAL' => ['elementary', 'secondary'],
        'GYMNASTICS' => ['elementary', 'secondary'], 'SWIMMING' => ['elementary', 'secondary'],
        'TABLE_TENNIS' => ['elementary', 'secondary'], 'TAEKWONDO' => ['elementary', 'secondary'],
        'PARA_BOCCE' => ['elementary', 'secondary'],
    ];

    public function run(): void
    {
        $this->call(SportsSeeder::class);

        foreach (SportsSeeder::SPORTS as [$code]) {
            $sport = Sport::query()->where('code', $code)->firstOrFail();
            $levels = self::LEVELS[$code] ?? [null];
            foreach ($levels as $index => $level) {
                $name = $level === null ? 'Open / Configuration Pending' : ucfirst($level).' Open';
                SportCategory::query()->updateOrCreate(
                    ['sport_id' => $sport->id, 'meet_sport_id' => null, 'slug' => str($name)->slug()],
                    [
                        'name' => $name, 'display_name' => $name, 'level' => $level,
                        'sex' => 'mixed', 'classification' => $sport->classification,
                        'competition_format' => $sport->is_team_sport ? 'team' : null,
                        'participation_notes' => 'Sex and detailed competition category require confirmation from the approved technical rules.',
                        'display_order' => $index + 1, 'active' => true,
                    ],
                );
            }
        }

        $volleyball = Sport::query()->where('code', 'VOLLEYBALL')->firstOrFail();
        foreach ([
            ['elementary', 'boys', 'Elementary Boys'],
            ['elementary', 'girls', 'Elementary Girls'],
            ['secondary', 'boys', 'Secondary Boys'],
            ['secondary', 'girls', 'Secondary Girls'],
        ] as $index => [$level, $sex, $name]) {
            SportCategory::query()->updateOrCreate(
                ['sport_id' => $volleyball->id, 'meet_sport_id' => null, 'slug' => str($name)->slug()],
                [
                    'name' => $name, 'display_name' => $name, 'level' => $level, 'sex' => $sex,
                    'classification' => $volleyball->classification, 'competition_format' => 'team',
                    'participation_notes' => null, 'display_order' => $index + 1, 'active' => true,
                ],
            );
        }

        $gymnastics = Sport::query()->where('code', 'GYMNASTICS')->firstOrFail();
        foreach (['WAG' => "Women's Artistic Gymnastics", 'MAG' => "Men's Artistic Gymnastics", 'RG' => 'Rhythmic Gymnastics'] as $code => $discipline) {
            foreach (['elementary', 'secondary'] as $index => $level) {
                $name = ucfirst($level).' '.$code;
                SportCategory::query()->updateOrCreate(
                    ['sport_id' => $gymnastics->id, 'meet_sport_id' => null, 'slug' => str($name)->slug()],
                    ['name' => $name, 'display_name' => $name, 'level' => $level, 'sex' => 'mixed',
                        'discipline' => $discipline, 'classification' => 'regular', 'display_order' => $index + 10, 'active' => true],
                );
            }
        }
    }
}
