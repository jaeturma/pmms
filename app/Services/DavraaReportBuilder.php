<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\DavraaReportGroup;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Turns a saved DAVRAA group into the template's row set and rebuilds its
 * member snapshots from canonical Athlete / User data. Nothing here
 * invents an LRN, school or district — a blank stays blank and is
 * surfaced as "incomplete" rather than guessed.
 */
class DavraaReportBuilder
{
    /**
     * @return array{last_name: ?string, given_names: ?string, middle_initial: ?string, lrn: ?string, school_name: ?string, district_name: ?string}
     */
    public function athleteSnapshot(Athlete $athlete): array
    {
        $athlete->loadMissing(['school.district', 'school.schoolDistrict', 'delegation.school.district']);
        $school = $athlete->school;

        return [
            'last_name' => $athlete->last_name,
            'given_names' => $this->givenNames($athlete->first_name, $athlete->name_extension),
            'middle_initial' => $this->middleInitial($athlete->middle_name),
            'lrn' => $athlete->lrn,
            'school_name' => $school?->name,
            'district_name' => $school?->schoolDistrict?->name
                ?? $school?->district?->name
                ?? $athlete->delegation?->district?->name,
        ];
    }

    /**
     * @return array{last_name: ?string, given_names: ?string, middle_initial: ?string, lrn: ?string, school_name: ?string, district_name: ?string}
     */
    public function coachSnapshot(User $coach): array
    {
        $coach->loadMissing(['personnel.school.district', 'personnel.school.schoolDistrict']);
        $personnel = $coach->personnel;
        $school = $personnel?->school;

        // Prefer the roster identity's split name fields; fall back to
        // splitting the single account display name.
        if ($personnel !== null && filled($personnel->last_name)) {
            [$last, $first, $middle] = [$personnel->last_name, $personnel->first_name, null];
        } else {
            [$last, $first, $middle] = $this->splitName($coach->name);
        }

        return [
            'last_name' => $last,
            'given_names' => $first,
            'middle_initial' => $this->middleInitial($middle),
            // Coaches have no LRN in the template.
            'lrn' => null,
            'school_name' => $school?->name,
            'district_name' => $school?->schoolDistrict?->name ?? $school?->district?->name,
        ];
    }

    /**
     * Ordered rows for the printed / exported report — from the stored
     * member snapshots, never a fresh query, so a finalized report stays
     * stable.
     *
     * @return array<int, array<string, mixed>>
     */
    public function rows(DavraaReportGroup $group): array
    {
        $group->loadMissing('members');

        return $group->members
            ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
            ->values()
            ->map(fn ($member, int $index): array => [
                'no' => $index + 1,
                'designation' => $member->designation->label(),
                'lrn' => $member->lrn ?? '',
                'last_name' => $member->last_name ?? '',
                'given_names' => $member->given_names ?? '',
                'middle_initial' => $member->middle_initial ?? '',
                'school_name' => $member->school_name ?? '',
                'district_name' => $member->district_name ?? '',
                'incomplete' => $member->missingFields(),
            ])
            ->all();
    }

    private function givenNames(?string $firstName, ?string $extension): ?string
    {
        return Str::of(trim(($firstName ?? '').' '.($this->isRealExtension($extension) ? $extension : '')))
            ->squish()
            ->value() ?: null;
    }

    private function middleInitial(?string $middleName): ?string
    {
        $middleName = trim((string) $middleName);

        if ($middleName === '' || in_array(mb_strtolower($middleName), ['n/a', 'none', '-'], true)) {
            return null;
        }

        return mb_strtoupper(mb_substr($middleName, 0, 1)).'.';
    }

    private function isRealExtension(?string $extension): bool
    {
        $extension = trim((string) $extension);

        return $extension !== '' && ! in_array(mb_strtolower($extension), ['n/a', 'none', '-'], true);
    }

    /**
     * A coach account only stores a single display name. Split it into
     * "Last, First Middle" heuristically ("Dela Cruz, Juan P" or plain
     * "Juan P Dela Cruz") without ever fabricating data.
     *
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function splitName(?string $name): array
    {
        $name = trim((string) $name);

        if ($name === '') {
            return [null, null, null];
        }

        if (str_contains($name, ',')) {
            [$last, $rest] = array_map('trim', explode(',', $name, 2));
            $parts = preg_split('/\s+/', $rest) ?: [];
            $middle = count($parts) > 1 ? (string) array_pop($parts) : null;

            return [$last ?: null, implode(' ', $parts) ?: null, $middle];
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        if (count($parts) === 1) {
            return [$parts[0], null, null];
        }

        $last = (string) array_pop($parts);
        $middle = count($parts) > 1 ? (string) array_pop($parts) : null;

        return [$last, implode(' ', $parts) ?: null, $middle];
    }
}
