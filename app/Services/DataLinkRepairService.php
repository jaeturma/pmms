<?php

namespace App\Services;

use App\Models\Athlete;
use App\Models\SportRosterMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataLinkRepairService
{
    public function link(User $user, SportRosterMember $roster, int $athleteId, int $expected, string $reason): void
    {
        DB::transaction(function () use ($user, $roster, $athleteId, $expected, $reason): void {
            $row = $this->lockedRoster($user, $roster, $expected);
            $athlete = Athlete::query()->lockForUpdate()->find($athleteId);
            if ($athlete === null) {
                throw ValidationException::withMessages(['athlete_id' => 'Select an available athlete. Archived identities must be reviewed in Athlete registration.']);
            }
            $this->apply($user, $row, $athlete, $reason);
        }, 3);
    }

    public function createAndLink(User $user, SportRosterMember $roster, array $data): Athlete
    {
        return DB::transaction(function () use ($user, $roster, $data): Athlete {
            $row = $this->lockedRoster($user, $roster, (int) $data['expected_athlete_id']);
            // Serialize repairs within the delegation, including identical-name checks.
            $row->delegation()->lockForUpdate()->firstOrFail();
            $duplicate = Athlete::withTrashed()->where(function ($identity) use ($data, $row) {
                $identity->where('lrn', $data['lrn'])->orWhere(fn ($names) => $names
                    ->where('delegation_id', $row->delegation_id)
                    ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower(trim($data['first_name']))])
                    ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower(trim($data['last_name']))])
                    ->whereDate('birthdate', $data['birthdate']));
            })->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['lrn' => 'A matching identity already exists, including archived records. Review it and link or restore the existing athlete.']);
            }
            $athlete = new Athlete(collect($data)->only([
                'first_name', 'middle_name', 'last_name', 'name_extension', 'sex', 'birthdate',
                'lrn', 'grade_level', 'age_division', 'school_id',
            ])->all());
            $athlete->delegation_id = $row->delegation_id;
            $athlete->registered_by = $user->id;
            $athlete->save();
            app(AuditLogger::class)->record('data_integrity.athlete_created', $athlete, [
                'roster_id' => $row->id, 'reason' => $data['reason'], 'after' => $athlete->getAttributes(),
            ], $user);
            $this->apply($user, $row, $athlete, $data['reason']);

            return $athlete;
        }, 3);
    }

    private function lockedRoster(User $user, SportRosterMember $roster, int $expected): SportRosterMember
    {
        $row = SportRosterMember::query()->lockForUpdate()->findOrFail($roster->id);
        abort_unless($row->meetSport !== null && app(DataIntegrityAccess::class)->allows($user, $row->meetSport->meet_id, $row->meetSport->sport_id), 403);
        if ($row->athlete_id !== $expected) {
            throw ValidationException::withMessages(['athlete_id' => 'This row changed since your preview. Reload and review it again.']);
        }
        if ($row->delegation === null || $row->meetSport->sport === null || $row->delegation->meet_id !== $row->meetSport->meet_id) {
            throw ValidationException::withMessages(['athlete_id' => 'Repair the canonical delegation/sport context before linking an athlete.']);
        }
        if ($row->athlete !== null && $row->athlete->delegation_id === $row->delegation_id) {
            throw ValidationException::withMessages(['athlete_id' => 'This roster link is already operational. Use normal athlete management for unrelated changes.']);
        }

        return $row;
    }

    private function apply(User $user, SportRosterMember $row, Athlete $athlete, string $reason): void
    {
        if ($athlete->delegation_id !== $row->delegation_id || ! $row->gender->accepts($athlete->sex) || ! $row->level->accepts($athlete->ageDivision())) {
            throw ValidationException::withMessages(['athlete_id' => 'The athlete must match the original delegation, level, and gender.']);
        }
        if (SportRosterMember::where('meet_sport_id', $row->meet_sport_id)->where('athlete_id', $athlete->id)->whereKeyNot($row->id)->exists()) {
            throw ValidationException::withMessages(['athlete_id' => 'This athlete already has a roster row for this sport. Review the duplicate; no records were merged or removed.']);
        }
        $before = $row->getAttributes();
        $row->athlete_id = $athlete->id;
        $row->save();
        app(AuditLogger::class)->record('data_integrity.roster_linked', $row, [
            'reason' => $reason, 'before' => $before, 'after' => $row->getAttributes(),
        ], $user);
    }
}
