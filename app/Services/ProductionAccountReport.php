<?php

namespace App\Services;

use App\Models\AccountProvision;
use App\Models\AuditLog;
use App\Models\DistrictSportsCoordinatorAssignment;
use App\Models\ManagementTeamMember;
use App\Models\MeetSportAssignment;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;

class ProductionAccountReport
{
    public function generate(?string $path = null): array
    {
        $path ??= base_path('docs/reports/testing/production-account-login-matrix.md');
        $provisions = AccountProvision::query()
            ->with([
                'linkedUser:id,username,role,must_change_password,disabled_at',
                'person:id,full_name,user_id',
                'person.meetSportAssignments.meetSport.sport:id,name',
                'person.managementTeamMemberships.managementTeam:id,name,source_code',
            ])
            ->orderBy('id')
            ->get();

        $lines = [
            '# PMMS Production Account Login Matrix',
            '',
            '> Development verification artifact. Passwords are intentionally excluded.',
            '',
            '| Name | Username | Primary Role | Sport / Assignment | Account Status | Must Change Password |',
            '|---|---|---|---|---|---|',
        ];

        foreach ($provisions as $provision) {
            $user = $provision->linkedUser ?? $provision->person->user;
            $sportAssignments = $provision->person->meetSportAssignments
                ->map(fn ($assignment): string => ($assignment->meetSport?->sport?->name ?? 'Unresolved sport').' — '.$assignment->role->label());
            $teamAssignments = $provision->person->managementTeamMemberships
                ->map(fn ($membership): string => ($membership->managementTeam?->name ?? 'Management Team').' — '.$membership->role_title);
            $assignments = collect($sportAssignments->all())
                ->merge($teamAssignments->all())
                ->filter()
                ->unique()
                ->join('; ');
            $status = $user?->disabled_at !== null ? 'DISABLED' : strtoupper($provision->status);

            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s |',
                $this->cell($provision->person->full_name),
                $this->cell($user?->username ?? $provision->suggested_username),
                $this->cell($user?->role?->label() ?? 'NO PROVISIONED PRODUCTION USER FOUND'),
                $this->cell($assignments ?: 'NO APPROVED ASSIGNMENT SCOPE'),
                $status,
                $user?->must_change_password ? 'YES' : 'NO',
            );
        }

        $summary = $this->summary($provisions);
        $lines[] = '';
        $lines[] = '## Verification Summary';
        $lines[] = '';
        foreach ($summary as $label => $value) {
            $lines[] = sprintf('- %s: %s', $label, $value);
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, implode(PHP_EOL, $lines).PHP_EOL);

        return $summary;
    }

    /** @return array<string, int> */
    private function summary(Collection $provisions): array
    {
        $roleCount = fn (array $roles): int => MeetSportAssignment::query()
            ->whereNotNull('user_id')->whereIn('role', $roles)->distinct()->count('user_id');
        $teamCount = fn (array $codes): int => ManagementTeamMember::query()
            ->whereNotNull('user_id')
            ->whereHas('managementTeam', fn ($query) => $query->whereIn('source_code', $codes))
            ->distinct()->count('user_id');

        return [
            'Total People' => Person::query()->count(),
            'Total Provisioning Records' => $provisions->count(),
            'Total Production Users' => User::query()->whereNotNull('username')->count(),
            'New Users Created' => AuditLog::query()->where('action', 'user.provisioned')->count(),
            'Existing Users Reused' => $provisions->whereIn('status', ['provisioned', 'active'])->count()
                - AuditLog::query()->where('action', 'user.provisioned')->count(),
            'Accounts Requiring Password Change' => User::query()->where('must_change_password', true)->count(),
            'Users with Multiple Roles' => Person::query()
                ->whereHas('meetSportAssignments', fn ($query) => $query->whereNotNull('user_id'))
                ->get()->filter(fn (Person $person) => $person->meetSportAssignments()->distinct()->count('role') > 1)->count(),
            'Users with Multiple Sport Assignments' => User::query()->whereHas('meetSportAssignments', fn ($query) => $query, '>', 1)->count(),
            'TM Accounts' => $roleCount(['tournament_manager', 'track_tournament_manager', 'field_tournament_manager', 'boys_tournament_manager', 'girls_tournament_manager', 'category_tournament_manager']),
            'Assistant TM Accounts' => $roleCount(['assistant_tournament_manager']),
            'Tournament Secretary Accounts' => $roleCount(['tournament_secretary']),
            'Tournament ICT Accounts' => $roleCount(['tournament_ict']),
            'TO Accounts' => $roleCount(['technical_official']),
            'DSC Accounts' => DistrictSportsCoordinatorAssignment::query()->whereHas('person', fn ($query) => $query->whereNotNull('user_id'))->distinct()->count('person_id'),
            'Event Secretariat Accounts' => $teamCount(['EVENT_SECRETARIAT']),
            'Medical Accounts' => $teamCount(['MEDICAL']),
            'DSAC Accounts' => $teamCount(['DSAC']),
            'ICT Accounts' => $teamCount(['CENTRAL_ICT', 'ICT', 'INFORMATION']),
            'Superadmin Accounts' => User::query()->whereNotNull('username')->where('role', 'admin')->count(),
            'Unresolved Provisioning Records' => $provisions->whereIn('status', ['pending', 'failed'])->count(),
            'Duplicate Candidates' => Person::query()->select('normalized_name')
                ->whereHas('accountProvision')->groupBy('normalized_name')->havingRaw('COUNT(*) > 1')->count(),
        ];
    }

    private function cell(string $value): string
    {
        return str_replace(['|', "\r", "\n"], ['\\|', ' ', ' '], $value);
    }
}
