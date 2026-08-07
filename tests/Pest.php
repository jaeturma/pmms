<?php

use App\Models\Delegation;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A school valid for this delegation: its own school (City), or any
 * school in its municipality (Province). Used by AthleteTest/PersonnelTest
 * (and later WP4 fixtures) wherever a valid `school_id` payload value is
 * needed for a given delegation.
 */
function schoolForDelegation(Delegation $delegation): School
{
    if ($delegation->school_id !== null) {
        return $delegation->school;
    }

    return School::factory()->create(['district_id' => $delegation->district_id]);
}

/**
 * The full sport_state a fresh basketball session starts with (WP
 * live-basketball) — clocks, roster/lineup, possession, and settings
 * defaults on top of the original {fouls_a, fouls_b}. Shared by
 * ScoringSessionTest and MatchRosterTest.
 *
 * @return array<string, mixed>
 */
function basketballInitialSportState(): array
{
    return [
        'fouls_a' => 0, 'fouls_b' => 0,
        'on_court_a' => [], 'on_court_b' => [],
        'possession' => null,
        'player_points' => [], 'player_fouls' => [],
        'game_clock_seconds' => 600, 'game_clock_updated_at' => null,
        'shot_clock_seconds' => 24, 'shot_clock_updated_at' => null,
        'minutes_per_period' => 10, 'shot_clock_duration' => 24,
        'team_color_a' => '#dc2626', 'team_color_b' => '#2563eb',
        'horn_sounded_at' => null, 'quarters' => 4,
    ];
}
