<?php

use App\Enums\EligibilityStatus;
use App\Enums\EntryStatus;
use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Models\Athlete;
use App\Models\CoachAssignmentRequest;
use App\Models\EligibilityReview;
use App\Models\Entry;
use App\Models\ManagementTeamMember;
use App\Models\MeetSportAssignment;
use App\Models\School;
use App\Models\User;
use Database\Seeders\CoachWorkflowDemoSeeder;
use Database\Seeders\DdOPAA2026FinalSeeder;
use Database\Seeders\DivisionRegistrySeeder;
use Database\Seeders\RoleAccountSeeder;
use Database\Seeders\SportsCatalogSeeder;

test('it seeds idempotent coach approval and athlete accreditation starter data', function () {
    $this->seed(DivisionRegistrySeeder::class);
    $this->seed(SportsCatalogSeeder::class);
    $this->seed(DdOPAA2026FinalSeeder::class);
    $this->seed(RoleAccountSeeder::class);
    School::factory()->create();
    $this->seed(CoachWorkflowDemoSeeder::class);
    $this->seed(CoachWorkflowDemoSeeder::class);

    $coach = User::query()->where('email', 'coach@pmms.test')->firstOrFail();
    $manager = User::query()->where('email', 'tournament.manager@pmms.test')->firstOrFail();
    $dsac = User::query()->where('email', 'viewer@pmms.test')->firstOrFail();
    $athlete = Athlete::query()->where('lrn', '900000000001')->firstOrFail();

    expect(CoachAssignmentRequest::query()->where('user_id', $coach->id)->where('status', 'pending')->count())->toBe(1)
        ->and(MeetSportAssignment::query()
            ->where('user_id', $manager->id)
            ->where('role', MeetSportAssignmentRole::TournamentManager->value)
            ->where('status', MeetSportAssignmentStatus::Active->value)
            ->count())->toBe(1)
        ->and(EligibilityReview::query()
            ->where('athlete_id', $athlete->id)
            ->where('status', EligibilityStatus::Approved->value)
            ->count())->toBe(1)
        ->and(Entry::query()
            ->where('athlete_id', $athlete->id)
            ->where('status', EntryStatus::Submitted->value)
            ->count())->toBe(1)
        ->and(ManagementTeamMember::query()
            ->where('user_id', $dsac->id)
            ->where('status', ManagementTeamMemberStatus::Active->value)
            ->count())->toBe(1);
});
