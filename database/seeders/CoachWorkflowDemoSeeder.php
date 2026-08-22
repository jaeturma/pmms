<?php

namespace Database\Seeders;

use App\Enums\DelegationStatus;
use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\MeetStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\CoachOnboardingRequest;
use App\Models\Delegation;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\Event;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Local starter records for the coach approval and accreditation workflow. */
class CoachWorkflowDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            $meet = Meet::query()->where('name', 'DdOPAA Meet 2026')->firstOrFail();
            $meet->forceFill(['status' => MeetStatus::RegistrationOpen, 'is_active' => true])->save();

            $sport = Sport::query()->where('active', true)->orderBy('display_order')->firstOrFail();
            $event = Event::query()->where('sport_id', $sport->id)->where('active', true)->orderBy('display_order')->firstOrFail();
            $meet->events()->syncWithoutDetaching([$event->id]);
            $meetSport = MeetSport::query()->firstOrCreate(
                ['meet_id' => $meet->id, 'sport_id' => $sport->id],
                ['active' => true],
            );

            // Use the production school registry instead of creating a
            // synthetic district or school that leaks into registration.
            $school = School::query()
                ->where('active', true)
                ->orderBy('name')
                ->firstOrFail();
            $delegation = Delegation::query()->firstOrCreate(
                ['meet_id' => $meet->id, 'school_id' => $school->id],
                ['head_name' => 'Coach Account', 'head_email' => 'coach@pmms.test'],
            );
            $delegation->forceFill(['status' => DelegationStatus::Approved])->save();

            $coach = User::query()->where('email', 'coach@pmms.test')->firstOrFail();
            CoachOnboardingRequest::query()->updateOrCreate(
                ['user_id' => $coach->id],
                ['status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'review_notes' => null],
            );
            CoachAssignmentRequest::query()->updateOrCreate(
                ['user_id' => $coach->id, 'event_id' => $event->id, 'delegation_id' => $delegation->id, 'school_id' => $school->id],
                ['meet_sport_id' => $meetSport->id, 'status' => 'pending', 'reviewed_by' => null, 'reviewed_at' => null, 'review_notes' => null],
            );

            $manager = User::query()->where('role', UserRole::TournamentManager->value)->firstOrFail();
            MeetSportAssignment::query()->updateOrCreate(
                ['meet_sport_id' => $meetSport->id, 'user_id' => $manager->id, 'role' => MeetSportAssignmentRole::TournamentManager->value],
                ['is_lead' => true, 'status' => MeetSportAssignmentStatus::Active->value],
            );

            $athlete = Athlete::query()->firstOrCreate(
                ['lrn' => '900000000001'],
                [
                    'delegation_id' => $delegation->id, 'school_id' => $school->id,
                    'first_name' => 'Alex', 'last_name' => 'Athlete',
                    'sex' => $event->gender->value === 'girls' ? 'female' : 'male',
                    'birthdate' => $event->age_division->value === 'elementary' ? now()->subYears(11) : now()->subYears(15),
                    'grade_level' => $event->age_division->value === 'elementary' ? 6 : 9,
                ],
            );
            EligibilityReview::query()->updateOrCreate(
                ['athlete_id' => $athlete->id, 'meet_id' => $meet->id],
                ['status' => EligibilityStatus::Approved->value, 'remarks' => 'Record ready for DSAC accreditation.'],
            );
            Entry::query()->updateOrCreate(
                ['athlete_id' => $athlete->id, 'event_id' => $event->id],
                ['delegation_id' => $delegation->id, 'status' => EntryStatus::Submitted->value],
            );

            $dsac = User::query()->where('email', 'viewer@pmms.test')->firstOrFail();
            $team = ManagementTeam::query()->updateOrCreate(
                ['meet_id' => $meet->id, 'source_code' => 'COACH_WORKFLOW_DEMO_DSAC'],
                ['team_type' => ManagementTeamType::DivisionScreeningAndAccreditation->value, 'name' => 'DSAC Team', 'status' => ManagementTeamStatus::Active->value],
            );
            ManagementTeamMember::query()->updateOrCreate(
                ['management_team_id' => $team->id, 'user_id' => $dsac->id],
                ['role_title' => 'DSAC Reviewer', 'is_head' => true, 'status' => ManagementTeamMemberStatus::Active->value],
            );
        });
    }
}
