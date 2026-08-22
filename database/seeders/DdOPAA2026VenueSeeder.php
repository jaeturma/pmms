<?php

namespace Database\Seeders;

use App\Models\CompetitionArea;
use App\Models\GameCoordinatorAssignment;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportVenue;
use App\Models\Person;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DdOPAA2026VenueSeeder extends Seeder
{
    private const SOURCE_SYSTEM = 'DDOPAA_2026_VENUE_SEED';

    public function run(): void
    {
        $rows = require database_path('data/ddopaa2026/venues.php');
        $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();

        DB::transaction(function () use ($rows, $meet): void {
            foreach ($rows as $sportRow) {
                $sport = Sport::query()->where('code', $sportRow['sport'])->first();

                // The final-import seeder is also used independently by tests
                // and maintenance commands. In that mode the sports catalog may
                // intentionally not have been loaded yet.
                if ($sport === null) {
                    continue;
                }
                $meetSport = MeetSport::query()->firstOrCreate(
                    ['meet_id' => $meet->id, 'sport_id' => $sport->id],
                    ['active' => true],
                );

                foreach ($sportRow['venues'] as $order => $venueRow) {
                    $venueCode = 'DDOPAA26-VENUE-'.Str::upper(Str::slug($venueRow['name'], '-'));
                    $venue = Venue::query()->where('source_code', $venueCode)->first();
                    if ($venue === null) {
                        $venue = Venue::query()->where('name', $venueRow['name'])->first();
                    }
                    if ($venue === null) {
                        $venue = Venue::query()->create([
                            'source_code' => $venueCode,
                            'source_system' => self::SOURCE_SYSTEM,
                            'name' => $venueRow['name'],
                            'address' => $venueRow['address'] ?? null,
                            'internal_notes' => $venueRow['internal_notes'] ?? null,
                            'readiness_status' => $venueRow['readiness'] ?? 'planned',
                            'source_venue_text' => $venueRow['source'],
                            'source_notes' => $sportRow['notes'] ?? null,
                            'active' => true,
                        ]);
                    } elseif ($venue->source_code === null) {
                        // Adopt a matching legacy master without changing any
                        // administrator-maintained venue field.
                        $venue->forceFill([
                            'source_code' => $venueCode,
                            'source_system' => self::SOURCE_SYSTEM,
                        ])->save();
                    }

                    $assignmentCode = 'DDOPAA26-MSV-'.$sportRow['sport'].'-'.Str::upper(Str::slug($venueRow['name'], '-'));
                    $coordinatorText = collect($venueRow['coordinators'] ?? [])->pluck(0)->join(' / ');
                    $contactText = collect($venueRow['coordinators'] ?? [])->pluck(1)->filter()->join(' / ');
                    MeetSportVenue::query()->firstOrCreate(
                        ['source_code' => $assignmentCode],
                        [
                            'meet_sport_id' => $meetSport->id,
                            'venue_id' => $venue->id,
                            'expected_area_count' => $venueRow['expected'] ?? ($venueRow['areas'][1] ?? null),
                            'notes' => $sportRow['notes'] ?? null,
                            'source_area_text' => $sportRow['area_text'],
                            'source_coordinator_text' => $coordinatorText ?: null,
                            'source_contact_text' => $contactText ?: null,
                            'import_status' => $sportRow['status'] ?? 'ready_to_seed',
                            'display_order' => $order + 1,
                            'status' => $venueRow['readiness'] ?? 'planned',
                        ],
                    );

                    if (isset($venueRow['areas'])) {
                        [$type, $count, $prefix] = $venueRow['areas'];
                        for ($number = 1; $number <= $count; $number++) {
                            $areaName = $count === 1 ? $prefix : $prefix.' '.$number;
                            CompetitionArea::query()->firstOrCreate(
                                ['source_code' => $venueCode.'-AREA-'.Str::upper(Str::slug($areaName, '-'))],
                                [
                                    'venue_id' => $venue->id,
                                    'code' => Str::upper(Str::slug($areaName, '-')),
                                    'name' => $areaName,
                                    'area_type' => $type,
                                    'display_order' => $number,
                                    'status' => $venueRow['readiness'] ?? 'planned',
                                ],
                            );
                        }
                    }

                    foreach ($venueRow['coordinators'] ?? [] as $position => [$name, $contact]) {
                        $normalized = $this->normalizeName($name);
                        $person = Person::query()->where('normalized_name', $normalized)->first()
                            ?? Person::query()->firstOrCreate(
                                ['source_key' => 'DDOPAA26-GC-'.Str::upper(Str::slug($name, '-'))],
                                ['full_name' => $name, 'normalized_name' => $normalized, 'source_flags' => ['venue_workbook']],
                            );

                        GameCoordinatorAssignment::query()->firstOrCreate(
                            ['source_code' => $assignmentCode.'-GC-'.Str::upper(Str::slug($name, '-'))],
                            [
                                'meet_sport_id' => $meetSport->id,
                                'venue_id' => $venue->id,
                                'person_id' => $person->id,
                                'is_lead' => $position === 0,
                                'status' => 'active',
                                'source_contact_text' => $contact,
                            ],
                        );
                    }

                }
            }
        });
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }
}
