<?php

namespace Database\Seeders;

use App\Enums\EntryStatus;
use App\Enums\GenderCategory;
use App\Enums\Sex;
use App\Models\Athlete;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\MatchRosterPlayer;
use App\Models\Meet;
use App\Models\School;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Adds two nine-player squads to live Basketball showcase matches so a
 * Sport ICT can attribute points and personal fouls to starters or bench
 * players. Local/testing only and idempotent.
 */
class BasketballPlayersSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->first();

        if ($meet === null) {
            $this->command?->warn('BasketballPlayersSeeder skipped: run Ddopaa2026ShowcaseSeeder first.');

            return;
        }

        $matches = EventMatch::query()
            ->where('meet_id', $meet->id)
            ->whereHas('event.sport', fn ($query) => $query->where('name', 'Basketball'))
            ->whereHas('scoringSessions')
            ->with(['event', 'scoringSessions' => fn ($query) => $query->latest('id')])
            ->get();

        foreach ($matches as $match) {
            $session = $match->scoringSessions->first();

            if ($session === null) {
                continue;
            }

            $squadA = $this->squad($meet, $match, $session->side_a_label, 'a');
            $squadB = $this->squad($meet, $match, $session->side_b_label, 'b');

            if ($squadA->isEmpty() || $squadB->isEmpty()) {
                $this->command?->warn("Basketball players skipped for match {$match->id}: its team labels do not match showcase delegations.");

                continue;
            }

            // A team match carries one representative entry per scheduled
            // side; its full playing squad lives in match_roster_players.
            $match->entries()->sync([$squadA->first()->id, $squadB->first()->id]);

            $rosterA = $this->roster($match, $squadA, 'a');
            $rosterB = $this->roster($match, $squadB, 'b');
            $state = $session->sport_state ?? [];
            $state['on_court_a'] = $rosterA->take(5)->pluck('id')->all();
            $state['on_court_b'] = $rosterB->take(5)->pluck('id')->all();
            $state['player_points'] ??= [];
            $state['player_fouls'] ??= [];
            $session->forceFill(['sport_state' => $state])->save();
        }

        $this->command?->info('Basketball player squads and rosters are ready for player scoring and fouls.');
    }

    /**
     * @return Collection<int, Entry>
     */
    private function squad(Meet $meet, EventMatch $match, string $teamLabel, string $side): Collection
    {
        $delegation = Delegation::query()
            ->where('meet_id', $meet->id)
            ->whereHas('district', fn ($query) => $query->where('name', $teamLabel))
            ->first();

        if ($delegation === null) {
            return collect();
        }

        $school = School::query()->where('district_id', $delegation->district_id)->where('active', true)->first();

        if ($school === null) {
            return collect();
        }

        $sex = $match->event->gender === GenderCategory::Girls ? Sex::Female : Sex::Male;
        $prefix = ucfirst($side) === 'A' ? 'Falcon' : 'Eagle';

        return collect(range(1, 9))->map(function (int $number) use ($delegation, $school, $match, $sex, $prefix): Entry {
            $athlete = Athlete::query()->firstOrCreate(
                [
                    'delegation_id' => $delegation->id,
                    'first_name' => $prefix,
                    'last_name' => "Player {$number}",
                ],
                [
                    'school_id' => $school->id,
                    'sex' => $sex,
                    'birthdate' => now()->subYears(16)->subDays($number),
                    'lrn' => (string) (100000000000 + abs(crc32("basketball-{$delegation->id}-{$number}")) % 899999999999),
                    'grade_level' => 10,
                ],
            );

            $entry = Entry::query()->firstOrCreate(
                ['athlete_id' => $athlete->id, 'event_id' => $match->event_id],
                ['delegation_id' => $delegation->id],
            );
            $entry->forceFill(['status' => EntryStatus::Confirmed])->save();

            return $entry;
        });
    }

    /**
     * @param  Collection<int, Entry>  $entries
     * @return Collection<int, MatchRosterPlayer>
     */
    private function roster(EventMatch $match, Collection $entries, string $side): Collection
    {
        return $entries->values()->map(fn (Entry $entry, int $index): MatchRosterPlayer => MatchRosterPlayer::query()->updateOrCreate(
            ['match_id' => $match->id, 'entry_id' => $entry->id],
            ['side' => $side, 'jersey_number' => (string) ($index + 4), 'is_starter' => $index < 5],
        ));
    }
}
