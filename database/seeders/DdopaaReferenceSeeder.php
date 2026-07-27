<?php

namespace Database\Seeders;

use App\Enums\AgeDivision;
use App\Enums\GenderCategory;
use App\Enums\MeetStatus;
use App\Models\District;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * DdOPAA 2025 Reference Dataset — WP1: Meet, Venue & Sports Catalog Setup.
 *
 * Local development and testing only. See
 * docs/phases/ddopaa-2025-reference-dataset/ for the full initiative plan
 * and docs/data-reference/ddopaa-2025-source-register.md for exactly what
 * each fact below is and isn't corroborated by — the Facebook page named
 * as this initiative's primary source turned out to be inaccessible, so
 * nothing here reaches VERIFIED_OFFICIAL; every record below is annotated
 * PARTIALLY_VERIFIED or SYNTHETIC_DERIVED per that register. Provenance is
 * documentation-only (owner decision) — these comments and the reference
 * docs are the entire mechanism, no new database columns.
 */
class DdopaaReferenceSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $meet = $this->meet();
        $this->nicknames();
        $venues = $this->venues();
        $events = $this->events();

        $meet->events()->syncWithoutDetaching($events->pluck('id'));
    }

    /**
     * PARTIALLY_VERIFIED: opened January 17, 2025 (source register #1, #4,
     * #5 — corroborated across three independent fetches, though not read
     * from primary text). SYNTHETIC_DERIVED: no closing date was found
     * anywhere, so `ends_at` is a plausible one-week span, not a verified
     * fact.
     */
    private function meet(): Meet
    {
        $meet = Meet::query()->firstOrCreate(
            ['name' => 'DdOPAA Meet 2025'],
            [
                'school_year' => '2024-2025',
                'starts_at' => '2025-01-17',
                'ends_at' => '2025-01-24',
                'venue' => 'Maragusan, Davao de Oro',
            ],
        );

        if ($meet->status !== MeetStatus::Active || ! $meet->is_published) {
            $meet->forceFill([
                'status' => MeetStatus::Active,
                'is_published' => true,
            ])->save();
        }

        return $meet;
    }

    /**
     * PARTIALLY_VERIFIED: five real delegation nicknames corroborated in
     * the source register (rows 4–6) — set only on these five real
     * District rows (already seeded by DivisionRegistrySeeder; never
     * creates new District rows). The other six municipalities are
     * deliberately left untouched — no nickname was found for them, and
     * none is invented.
     */
    private function nicknames(): void
    {
        $nicknames = [
            'Nabunturan' => 'Black Mamba',
            'Montevista' => 'Blazing Fighters',
            'New Bataan' => 'Rock Wreckers',
            'Mawab' => 'Pick Hammer',
            'Maragusan' => 'Maroon Knights',
        ];

        foreach ($nicknames as $municipality => $nickname) {
            District::query()
                ->where('name', $municipality)
                ->update(['nickname' => $nickname]);
        }
    }

    /**
     * PARTIALLY_VERIFIED: "Maragusan Grandstand Arena" is named in the
     * source register (#1, #4, #5) as the opening-ceremony venue.
     * SYNTHETIC_DEMO: the other two venues — no source names a specific
     * gymnasium or pool, but the standard-dataset and live-scoring WPs
     * need somewhere to schedule indoor/aquatic events.
     *
     * @return Collection<int, Venue>
     */
    private function venues(): Collection
    {
        $venues = [
            'Maragusan Grandstand Arena' => 'Maragusan, Davao de Oro',
            'Maragusan Sports Complex Gymnasium' => 'Maragusan, Davao de Oro',
            'Maragusan Municipal Pool' => 'Maragusan, Davao de Oro',
        ];

        return collect($venues)->map(
            fn (string $address, string $name) => Venue::query()->firstOrCreate(
                ['name' => $name],
                ['address' => $address, 'active' => true],
            ),
        )->values();
    }

    /**
     * Sports: Athletics, Basketball, Volleyball, Swimming, Gymnastics
     * already exist (SportsCatalogSeeder). Boxing is added here — a
     * supported live-scoring board type since Phase 7, but never part of
     * the seeded catalog list.
     *
     * Events: the existing "Basketball" (Boys, Secondary, team) event
     * from the earlier live-scoreboard demo addition is reused, not
     * duplicated. Every event below is annotated with its own
     * classification — existence of an event that directly matches a
     * corroborated fact is PARTIALLY_VERIFIED; its gender-paired
     * counterpart (added for a realistic, complete program) is
     * SYNTHETIC_DERIVED. All Secondary-level only — the corroborated
     * facts are all about secondary-level delegation teams, and
     * Elementary-division breadth is already demonstrated elsewhere in
     * the catalog (Athletics).
     *
     * @return Collection<int, Event>
     */
    private function events(): Collection
    {
        $basketball = Sport::query()->where('name', 'Basketball')->firstOrFail();
        $volleyball = Sport::query()->where('name', 'Volleyball')->firstOrFail();
        $swimming = Sport::query()->where('name', 'Swimming')->firstOrFail();
        $gymnastics = Sport::query()->where('name', 'Gymnastics')->firstOrFail();
        $boxing = Sport::query()->firstOrCreate(['name' => 'Boxing']);

        // [sport, name, gender, team, max_entries, classification note only — not persisted]
        $definitions = [
            // PARTIALLY_VERIFIED: source register #4 — Montevista won
            // 3x3 Basketball (Boys).
            [$basketball, '3x3 Basketball', GenderCategory::Boys, true, 5],
            // SYNTHETIC_DERIVED: gender-paired counterpart, not verified.
            [$basketball, '3x3 Basketball', GenderCategory::Girls, true, 5],
            // SYNTHETIC_DERIVED: completes the existing Boys Basketball
            // event's gender pairing.
            [$basketball, 'Basketball', GenderCategory::Girls, true, 12],

            // PARTIALLY_VERIFIED: source register #4, #6 — Nabunturan won
            // Women's Volleyball; Mawab beat Maragusan in a Volleyball
            // semifinal.
            [$volleyball, 'Volleyball', GenderCategory::Girls, true, 12],
            // SYNTHETIC_DERIVED: gender-paired counterpart, not verified.
            [$volleyball, 'Volleyball', GenderCategory::Boys, true, 12],

            // PARTIALLY_VERIFIED: source register #4 — New Bataan won
            // Men's Artistic Gymnastics.
            [$gymnastics, 'Artistic Gymnastics', GenderCategory::Boys, false, 3],
            // SYNTHETIC_DERIVED: gender-paired counterpart, not verified.
            [$gymnastics, 'Artistic Gymnastics', GenderCategory::Girls, false, 3],

            // SYNTHETIC_DERIVED: swimming is only confirmed as an
            // included sport (the government-source summary), no
            // specific event was found — a plausible baseline event, not
            // a verified program.
            [$swimming, '50 Meter Freestyle', GenderCategory::Boys, false, 3],
            [$swimming, '50 Meter Freestyle', GenderCategory::Girls, false, 3],

            // PARTIALLY_VERIFIED: source register #6 — a "Boxing
            // Championship" is explicitly named, Nabunturan won 4 golds
            // in it.
            [$boxing, 'Boxing', GenderCategory::Boys, false, 3],
            // SYNTHETIC_DERIVED: gender-paired counterpart, not verified.
            [$boxing, 'Boxing', GenderCategory::Girls, false, 3],
        ];

        $events = collect($definitions)->map(
            fn (array $definition) => Event::query()->firstOrCreate(
                [
                    'sport_id' => $definition[0]->id,
                    'name' => $definition[1],
                    'gender' => $definition[2]->value,
                    'age_division' => AgeDivision::Secondary->value,
                ],
                [
                    'is_team_event' => $definition[3],
                    'max_entries_per_delegation' => $definition[4],
                ],
            ),
        );

        // Include the pre-existing Boys Basketball event (created outside
        // this seeder) so it's also attached to the DdOPAA meet.
        $existingBasketball = Event::query()
            ->where('sport_id', $basketball->id)
            ->where('name', 'Basketball')
            ->where('gender', GenderCategory::Boys->value)
            ->first();

        if ($existingBasketball !== null) {
            $events->push($existingBasketball);
        }

        return $events->values();
    }
}
