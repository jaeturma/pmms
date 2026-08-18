<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportsSeeder extends Seeder
{
    public const array SPORTS = [
        ['ARCHERY', 'Archery', 'regular', false], ['ARNIS', 'Arnis', 'regular', false],
        ['ATHLETICS', 'Athletics', 'regular', false], ['BADMINTON', 'Badminton', 'regular', false],
        ['BASEBALL', 'Baseball', 'regular', true], ['BASKETBALL', 'Basketball', 'regular', true],
        ['BASKETBALL_3X3', 'Basketball 3x3', 'regular', true], ['BILLIARDS', 'Billiards', 'regular', false],
        ['BOXING', 'Boxing', 'regular', false], ['CHESS', 'Chess', 'regular', false],
        ['DANCESPORT', 'Dancesports', 'regular', false], ['FOOTBALL', 'Football', 'regular', true],
        ['FUTSAL', 'Futsal', 'regular', true], ['GYMNASTICS', 'Gymnastics', 'regular', false],
        ['PENCAK_SILAT', 'Pencak Silat', 'regular', false], ['SEPAK_TAKRAW', 'Sepak Takraw', 'regular', true],
        ['SOFTBALL', 'Softball', 'regular', true], ['SWIMMING', 'Swimming', 'regular', false],
        ['TABLE_TENNIS', 'Table Tennis', 'regular', false], ['TAEKWONDO', 'Taekwondo', 'regular', false],
        ['TENNIS', 'Tennis', 'regular', false], ['VOLLEYBALL', 'Volleyball', 'regular', true],
        ['WEIGHTLIFTING', 'Weightlifting', 'regular', false], ['WRESTLING', 'Wrestling', 'regular', false],
        ['WUSHU', 'Wushu', 'regular', false], ['PARA_BOCCE', 'Bocce', 'paragames', false],
        ['PARA_GOALBALL', 'Goalball', 'paragames', true], ['PARA_ATHLETICS', 'Para Athletics', 'paragames', false],
        ['PARA_SWIMMING', 'Para Swimming', 'paragames', false],
    ];

    public function run(): void
    {
        $regularOrder = $paraOrder = 0;
        foreach (self::SPORTS as [$code, $name, $classification, $team]) {
            $order = $classification === 'regular' ? ++$regularOrder : ++$paraOrder;
            // Adopt pre-code catalog rows by canonical name before applying
            // the stable code. This handles databases migrated from the
            // legacy name-only catalog without violating its name unique key.
            $sport = Sport::query()->where('code', $code)->orWhere('name', $name)->first()
                ?? new Sport;

            $sport->forceFill([
                'code' => $code,
                'name' => $name, 'slug' => str($name)->slug(), 'classification' => $classification,
                'icon_key' => str($name)->slug(), 'is_team_sport' => $team, 'active' => true,
                'display_order' => $order,
                'short_description' => $name.' competition configured for the DdOPAA provincial sports program.',
            ])->save();
        }
    }
}
