<?php

namespace Database\Seeders;

use App\Models\CompetitionArea;
use App\Models\GameCoordinatorAssignment;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportVenue;
use App\Models\Person;
use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\SportCategoryCompetitionArea;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
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
                    $legacyNames = $venueRow['legacy_names'] ?? [];
                    $legacyCodes = collect($legacyNames)
                        ->map(fn (string $name): string => 'DDOPAA26-VENUE-'.Str::upper(Str::slug($name, '-')));
                    $venue = Venue::query()->where('name', $venueRow['name'])->first();
                    if ($venue === null) {
                        $venue = Venue::query()->where('source_code', $venueCode)->first();
                    }
                    if ($venue === null && $legacyCodes->isNotEmpty()) {
                        $venue = Venue::query()->whereIn('source_code', $legacyCodes)->first();
                    }
                    if ($venue === null && $legacyNames !== []) {
                        $venue = Venue::query()->whereIn('name', $legacyNames)->first();
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
                    } elseif ($venue->source_code === null || $legacyCodes->contains($venue->source_code)) {
                        // Adopt a matching legacy master without changing any
                        // administrator-maintained fields. A legacy source name
                        // is changed only while it still has the imported value.
                        $venue->forceFill([
                            'source_code' => $venueCode,
                            'source_system' => self::SOURCE_SYSTEM,
                            'name' => in_array($venue->name, $legacyNames, true) ? $venueRow['name'] : $venue->name,
                        ])->save();
                    }

                    $assignmentCode = 'DDOPAA26-MSV-'.$sportRow['sport'].'-'.Str::upper(Str::slug($venueRow['name'], '-'));
                    $legacyAssignmentCodes = collect($legacyNames)
                        ->map(fn (string $name): string => 'DDOPAA26-MSV-'.$sportRow['sport'].'-'.Str::upper(Str::slug($name, '-')));
                    $coordinatorText = collect($venueRow['coordinators'] ?? [])->pluck(0)->join(' / ');
                    $contactText = collect($venueRow['coordinators'] ?? [])->pluck(1)->filter()->join(' / ');
                    $meetSportVenue = MeetSportVenue::query()->where('source_code', $assignmentCode)->first()
                        ?? MeetSportVenue::query()->whereIn('source_code', $legacyAssignmentCodes)->first();
                    if ($meetSportVenue === null) {
                        $meetSportVenue = MeetSportVenue::query()->create([
                            'source_code' => $assignmentCode,
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
                        ]);
                    } elseif ($legacyAssignmentCodes->contains($meetSportVenue->source_code)) {
                        $meetSportVenue->forceFill([
                            'source_code' => $assignmentCode,
                            'venue_id' => $venue->id,
                        ])->save();
                    }

                    $areas = collect();
                    if (isset($venueRow['areas'])) {
                        [$type, $count, $prefix] = $venueRow['areas'];
                        for ($number = 1; $number <= $count; $number++) {
                            $areaName = $count === 1 ? $prefix : $prefix.' '.$number;
                            $areaCode = $venueCode.'-AREA-'.Str::upper(Str::slug($areaName, '-'));
                            $area = CompetitionArea::query()->where('source_code', $areaCode)->first()
                                ?? CompetitionArea::query()->where('venue_id', $venue->id)->where('name', $areaName)->first();

                            if ($area === null && $count === 1 && $areaName === 'Court 1') {
                                $area = CompetitionArea::query()
                                    ->where('venue_id', $venue->id)
                                    ->where('name', 'Main Court')
                                    ->first();
                            }

                            if ($area === null) {
                                $area = CompetitionArea::query()->create([
                                    'source_code' => $areaCode,
                                    'venue_id' => $venue->id,
                                    'code' => Str::upper(Str::slug($areaName, '-')),
                                    'name' => $areaName,
                                    'area_type' => $type,
                                    'display_order' => $number,
                                    'status' => $venueRow['readiness'] ?? 'planned',
                                ]);
                            } elseif ($area->name === 'Main Court' && $areaName === 'Court 1') {
                                $area->forceFill(['source_code' => $areaCode, 'code' => 'COURT-1', 'name' => 'Court 1'])->save();
                            }

                            $areas->push($area);
                        }
                    }

                    if ($sportRow['sport'] === 'VOLLEYBALL') {
                        $this->seedVolleyballCategoryAvailability($meetSport, $venue, $areas);
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

    /** @param Collection<int, CompetitionArea> $areas */
    private function seedVolleyballCategoryAvailability(MeetSport $meetSport, Venue $venue, $areas): void
    {
        $categoryNames = match ($venue->name) {
            'Compostela Sports Complex' => ['Secondary Boys', 'Secondary Girls'],
            'Purok 6' => ['Elementary Girls'],
            'Purok 7' => ['Elementary Boys'],
            default => [],
        };

        foreach ($categoryNames as $categoryName) {
            [$level, $sex] = explode(' ', strtolower($categoryName));
            $category = SportCategory::query()->firstOrCreate(
                [
                    'sport_id' => $meetSport->sport_id,
                    'meet_sport_id' => null,
                    'slug' => Str::slug($categoryName),
                ],
                [
                    'name' => $categoryName,
                    'display_name' => $categoryName,
                    'level' => $level,
                    'sex' => $sex,
                    'classification' => 'regular',
                    'competition_format' => 'team',
                    'active' => true,
                ],
            );

            foreach ($areas as $area) {
                SportCategoryCompetitionArea::query()->firstOrCreate(
                    ['source_code' => 'DDOPAA26-VB-'.Str::upper(Str::slug($categoryName.'-'.$venue->name.'-'.$area->name, '-'))],
                    [
                        'meet_sport_id' => $meetSport->id,
                        'sport_category_id' => $category->id,
                        'venue_id' => $venue->id,
                        'competition_area_id' => $area->id,
                        'status' => 'active',
                    ],
                );
            }
        }
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->toString();
    }
}
