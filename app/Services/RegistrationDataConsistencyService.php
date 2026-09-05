<?php

namespace App\Services;

use App\Enums\PersonnelRole;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Entry;
use App\Models\EventMatch;
use App\Models\Personnel;
use App\Models\TeamEntryMember;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RegistrationDataConsistencyService
{
    /** @return Collection<int, array<string, mixed>> */
    public function issues(?Collection $sportIds = null): Collection
    {
        return $this->athleteIssues($sportIds)->concat($this->coachIssues($sportIds))->concat($this->gameIssues($sportIds))->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function athleteIssues(?Collection $sportIds = null): Collection
    {
        return Athlete::query()
            ->when($sportIds !== null, fn ($athletes) => $athletes->where(fn ($scope) => $scope
                ->whereHas('entries.event', fn ($events) => $events->whereIn('sport_id', $sportIds))
                ->orWhereHas('sportRosterMemberships.meetSport', fn ($meetSports) => $meetSports->whereIn('sport_id', $sportIds))))
            ->with(['delegation.meet', 'delegation.school', 'delegation.district', 'school.district', 'school.schoolDistrict', 'coaches'])
            ->get()->flatMap(function (Athlete $athlete): array {
                $issues = [];
                $add = function (string $code, string $problem, string $recommendation, ?array $repair = null) use (&$issues, $athlete): void {
                    $issues[] = $this->row('athlete', $athlete->id, $athlete->fullName(), $code, $problem, $recommendation, $repair, route('athletes.edit', $athlete));
                };
                if ($athlete->delegation === null) {
                    $add('missing_delegation', 'The athlete has no valid delegation.', 'Select the correct delegation manually.');

                    return $issues;
                }
                if ($athlete->school !== null && $athlete->delegation->school_id !== null && $athlete->school_id !== $athlete->delegation->school_id) {
                    $add('school_mismatch', 'The athlete school differs from the school delegation.', 'Use the delegation school or move the athlete to the correct delegation.', ['code' => 'use_delegation_school', 'label' => 'Use delegation school']);
                }
                if ($athlete->school && $athlete->school->school_district_id !== null && $athlete->school->schoolDistrict?->district_id !== $athlete->school->district_id) {
                    $add('school_district_mismatch', 'The school district belongs to a different municipality than the school.', 'Repair the School master record before extracting this athlete.');
                }
                $derivedDivision = $athlete->grade_level <= 6 ? 'elementary' : 'secondary';
                if (in_array($athlete->age_division?->value, ['elementary', 'secondary'], true) && $athlete->age_division->value !== $derivedDivision) {
                    $add('division_mismatch', 'The saved division conflicts with the athlete grade level.', 'Recalculate the division from grade level.', ['code' => 'derive_division', 'label' => 'Recalculate division']);
                }

                return $issues;
            });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function coachIssues(?Collection $sportIds = null): Collection
    {
        $personnel = Personnel::query()->whereIn('role', [PersonnelRole::Coach->value, PersonnelRole::AssistantCoach->value])
            ->when($sportIds !== null, fn ($coaches) => $coaches->whereHas('sports', fn ($sports) => $sports->whereIn('sports.id', $sportIds)))
            ->with(['delegation.meet', 'delegation.school', 'delegation.district', 'school.district', 'sports', 'user'])->get()
            ->flatMap(function (Personnel $coach): array {
                $issues = [];
                $add = function (string $code, string $problem, string $recommendation, ?array $repair = null) use (&$issues, $coach): void {
                    $issues[] = $this->row('coach', $coach->id, $coach->fullName(), $code, $problem, $recommendation, $repair, route('personnel.index', ['search' => $coach->fullName()]));
                };
                if ($coach->delegation === null) {
                    $add('missing_delegation', 'The coach has no valid delegation.', 'Select the correct delegation manually.');

                    return $issues;
                }
                if ($coach->school === null && $coach->delegation->school_id !== null) {
                    $add('missing_school', 'The school is missing from this school-delegation coach.', 'Use the delegation school.', ['code' => 'use_delegation_school', 'label' => 'Use delegation school']);
                } elseif ($coach->school && $coach->delegation->school_id !== null && $coach->school_id !== $coach->delegation->school_id) {
                    $add('school_mismatch', 'The coach school differs from the school delegation.', 'Use the delegation school or move the coach to the correct delegation.', ['code' => 'use_delegation_school', 'label' => 'Use delegation school']);
                } elseif ($coach->school && $coach->delegation->district_id !== null && $coach->school->district_id !== $coach->delegation->district_id) {
                    $add('municipality_mismatch', 'The coach school belongs to a different municipality than the delegation.', 'Select a school within the delegation municipality.');
                }
                if ($coach->sports->isEmpty()) {
                    $add('missing_sport', 'The coach has no assigned sport.', 'Assign at least one sport before extracting sport-specific coach data.');
                }
                if ($coach->user && ! $coach->user->hasRole(UserRole::Coach)) {
                    $add('account_role_mismatch', 'The linked login account does not have the Coach role.', 'Correct the user role or link the proper coach account.');
                }

                return $issues;
            });

        $unlinkedAccounts = $sportIds === null
            ? User::query()->where('role', UserRole::Coach->value)->whereDoesntHave('personnel')->get()
                ->map(fn (User $user): array => $this->row('coach_account', $user->id, $user->name, 'missing_personnel_link', 'The coach login has no linked personnel record.', 'Link this account to the proper coach personnel record.', null, route('coach.assignment-requests.index')))
            : collect();

        return $personnel->concat($unlinkedAccounts);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function gameIssues(?Collection $sportIds = null): Collection
    {
        $entries = Entry::query()
            ->when($sportIds !== null, fn ($entries) => $entries->whereHas('event', fn ($events) => $events->whereIn('sport_id', $sportIds)))
            ->with(['athlete.delegation', 'event', 'delegation'])->get()
            ->flatMap(function (Entry $entry): array {
                $issues = [];
                if ($entry->athlete === null) {
                    $issues[] = $this->row('game', $entry->id, "Entry #{$entry->id}", 'missing_athlete', 'The individual entry has no valid athlete.', 'Withdraw or relink this entry before extracting participants.', null, route('entries.index'));
                } elseif ($entry->delegation_id !== $entry->athlete->delegation_id) {
                    $issues[] = $this->row('game', $entry->id, "Entry #{$entry->id}", 'entry_delegation_mismatch', 'The entry and athlete belong to different delegations.', 'Open Entries and select the athlete under the correct delegation.', null, route('entries.index'));
                }
                if ($entry->event?->is_team_event) {
                    $issues[] = $this->row('game', $entry->id, "Entry #{$entry->id}", 'individual_entry_for_team_event', 'A team event is being represented as an individual entry.', 'Create or use one delegation Team Entry and attach athletes as its members.', null, route('entries.index'));
                }

                return $issues;
            });

        $members = TeamEntryMember::query()
            ->when($sportIds !== null, fn ($members) => $members->whereHas('teamEntry.event', fn ($events) => $events->whereIn('sport_id', $sportIds)))
            ->with(['entry', 'teamEntry'])->get()
            ->filter(fn (TeamEntryMember $member): bool => $member->entry === null || $member->teamEntry === null
                || $member->entry->delegation_id !== $member->teamEntry->delegation_id
                || $member->entry->event_id !== $member->teamEntry->event_id)
            ->map(fn (TeamEntryMember $member): array => $this->row('game', $member->id, "Team member #{$member->id}", 'team_member_mismatch', 'The athlete entry does not match the team entry delegation or event.', 'Remove the member and select an entry from the same delegation and event.', null, route('entries.index')));

        $matches = EventMatch::query()
            ->when($sportIds !== null, fn ($matches) => $matches->whereHas('event', fn ($events) => $events->whereIn('sport_id', $sportIds)))
            ->withCount(['entries', 'teamEntries'])->with('event')->get()
            ->filter(fn (EventMatch $match): bool => ($match->event?->is_team_event && $match->entries_count > 0)
                || (! $match->event?->is_team_event && $match->team_entries_count > 0))
            ->map(fn (EventMatch $match): array => $this->row('game', $match->id, "Match #{$match->id}", 'match_participant_type_mismatch', $match->event->is_team_event ? 'A team match contains individual match entries.' : 'An individual match contains delegation team entries.', 'Open the match and reselect participants using the event participant type.', null, route('matches.index')));

        return $entries->concat($members)->concat($matches);
    }

    /** @return array<string, mixed> */
    private function row(string $type, int $id, string $name, string $code, string $problem, string $recommendation, ?array $repair, string $manualUrl): array
    {
        return compact('type', 'id', 'name', 'code', 'problem', 'recommendation', 'repair', 'manualUrl');
    }

    public function repair(string $type, int $id, string $code): void
    {
        if ($type === 'athlete') {
            $record = Athlete::query()->with(['delegation.school'])->findOrFail($id);
            if ($code === 'use_delegation_school' && $record->delegation?->school_id) {
                $record->forceFill(['school_id' => $record->delegation->school_id])->save();

                return;
            }
            if ($code === 'derive_division') {
                $record->forceFill(['age_division' => $record->grade_level <= 6 ? 'elementary' : 'secondary'])->save();

                return;
            }
        }
        if ($type === 'coach' && $code === 'use_delegation_school') {
            $record = Personnel::query()->with('delegation.school')->findOrFail($id);
            if ($record->delegation?->school_id) {
                $record->forceFill(['school_id' => $record->delegation->school_id])->save();

                return;
            }
        }
        throw ValidationException::withMessages(['repair' => __('This record has no unambiguous automatic repair. Open it and select the proper information manually.')]);
    }
}
