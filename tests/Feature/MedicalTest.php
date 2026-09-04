<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MedicalClearanceStatus;
use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MedicalAccessLog;
use App\Models\MedicalClearance;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Database\QueryException;
use Inertia\Testing\AssertableInertia;

/**
 * Medical (WP-REALIGN-12) — deliberately minimal (no case-management
 * history), and the one domain in this whole WP-REALIGN-0x series where
 * Organizer does NOT get unconditional manage access — only Medical Team
 * or Admin do. See docs/medical-drrm.md.
 */
function medicalTeamMember(?Meet $meet = null, bool $isHead = false): User
{
    $meet ??= Meet::factory()->create();
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'team_type' => ManagementTeamType::Medical]);
    $member = ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'status' => ManagementTeamMemberStatus::Active,
        'is_head' => $isHead,
    ]);

    return $member->user;
}

// --- Model relationships ---

test('a meet has many medical clearances, an athlete has one clearance', function () {
    $meet = Meet::factory()->create();
    $athlete = Athlete::factory()->create();
    $clearance = MedicalClearance::factory()->create(['meet_id' => $meet->id, 'athlete_id' => $athlete->id]);

    expect($meet->medicalClearances()->first()->id)->toBe($clearance->id)
        ->and($athlete->medicalClearance->id)->toBe($clearance->id);
});

test('a person cannot have two clearance records for the same meet', function () {
    $meet = Meet::factory()->create();
    $athlete = Athlete::factory()->create();
    MedicalClearance::factory()->create(['meet_id' => $meet->id, 'athlete_id' => $athlete->id]);

    expect(fn () => MedicalClearance::factory()->create(['meet_id' => $meet->id, 'athlete_id' => $athlete->id]))
        ->toThrow(QueryException::class);
});

// --- MedicalClearanceController::index() ---

test('guests are redirected from the medical page', function () {
    $this->get('/medical')->assertRedirect('/login');
});

test('a plain viewer cannot view the medical page', function () {
    $this->actingAs(User::factory()->create())
        ->get('/medical')
        ->assertForbidden();
});

test('an organizer sees the aggregate status but not raw detail', function () {
    $meet = Meet::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => Delegation::factory()->create(['meet_id' => $meet->id])]);
    MedicalClearance::factory()->create([
        'meet_id' => $meet->id,
        'athlete_id' => $athlete->id,
        'status' => MedicalClearanceStatus::Restricted,
        'conditions' => 'Asthma',
        'notes' => 'Carries an inhaler.',
    ]);

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/medical')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('medical/index')
            ->has('clearances', 1)
            ->where('clearances.0.status', MedicalClearanceStatus::Restricted->value)
            ->where('clearances.0.can_view_detail', false)
            ->where('clearances.0.conditions', null)
            ->where('clearances.0.notes', null));
});

test('an admin sees raw detail everywhere; a medical team member sees it only for their own meet', function () {
    $meet = Meet::factory()->create();
    $otherMeet = Meet::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => Delegation::factory()->create(['meet_id' => $meet->id])]);
    MedicalClearance::factory()->create(['meet_id' => $meet->id, 'athlete_id' => $athlete->id, 'conditions' => 'Asthma']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/medical')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('clearances.0.can_view_detail', true)
            ->where('clearances.0.conditions', 'Asthma'));

    $this->actingAs(medicalTeamMember($meet))
        ->get('/medical')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('clearances.0.can_view_detail', true)
            ->where('clearances.0.conditions', 'Asthma'));

    // A medical team member of a different meet can still reach the
    // page (viewAny only requires Active membership somewhere), but the
    // accessible-meets scoping means this other meet's data never shows.
    $this->actingAs(medicalTeamMember($otherMeet))
        ->get('/medical?meet_id='.$meet->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->has('clearances', 0));
});

// --- MedicalClearanceController mutations — the real 3-tier departure ---

test('organizers cannot create or update a clearance record — only medical team or admin', function () {
    $meet = Meet::factory()->create();
    $athlete = Athlete::factory()->create(['delegation_id' => Delegation::factory()->create(['meet_id' => $meet->id])]);

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/medical-clearances', [
            'meet_id' => $meet->id,
            'athlete_id' => $athlete->id,
            'status' => MedicalClearanceStatus::Pending->value,
        ])
        ->assertForbidden();

    $clearance = MedicalClearance::factory()->create(['meet_id' => $meet->id]);

    $this->actingAs(User::factory()->organizer()->create())
        ->put("/medical-clearances/{$clearance->id}", ['status' => MedicalClearanceStatus::Cleared->value])
        ->assertForbidden();
});

test('a medical team member can create a clearance record for their own meet', function () {
    $meet = Meet::factory()->create();
    $delegation = Delegation::factory()->create(['meet_id' => $meet->id]);
    $athlete = Athlete::factory()->create(['delegation_id' => $delegation->id]);

    $this->actingAs(medicalTeamMember($meet))
        ->post('/medical-clearances', [
            'meet_id' => $meet->id,
            'athlete_id' => $athlete->id,
            'status' => MedicalClearanceStatus::Pending->value,
            'consent_confirmed' => true,
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('medical_clearances', [
        'meet_id' => $meet->id,
        'athlete_id' => $athlete->id,
        'consent_confirmed' => true,
    ]);
    expect(AuditLog::query()->where('action', 'medical_clearance.created')->exists())->toBeTrue();
});

test('admins can update a clearance record', function () {
    $clearance = MedicalClearance::factory()->create(['status' => MedicalClearanceStatus::Pending]);

    $this->actingAs(User::factory()->admin()->create())
        ->put("/medical-clearances/{$clearance->id}", [
            'status' => MedicalClearanceStatus::Cleared->value,
            'consent_confirmed' => true,
        ])
        ->assertSessionHasNoErrors();

    expect($clearance->fresh())
        ->status->toBe(MedicalClearanceStatus::Cleared)
        ->consent_confirmed->toBeTrue()
        ->and($clearance->fresh()->consent_confirmed_at)->not->toBeNull();

    expect(AuditLog::query()->where('action', 'medical_clearance.updated')->exists())->toBeTrue();
});

test('system admin can accept one or selected medical records in one click', function () {
    $meet = Meet::factory()->active()->create();
    $first = MedicalClearance::factory()->create(['meet_id' => $meet->id, 'status' => MedicalClearanceStatus::Pending]);
    $second = MedicalClearance::factory()->create(['meet_id' => $meet->id, 'status' => MedicalClearanceStatus::ForEvaluation]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch("/medical-clearances/{$first->id}/clear")
        ->assertRedirect()->assertSessionDoesntHaveErrors();
    $this->actingAs($admin)->patch('/medical-clearances/bulk-clear', [
        'clearance_ids' => [$second->id],
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($first->fresh()->status)->toBe(MedicalClearanceStatus::Cleared)
        ->and($second->fresh()->status)->toBe(MedicalClearanceStatus::Cleared)
        ->and(AuditLog::query()->where('action', 'medical_clearance.accepted')->count())->toBe(2);
});

test('medical team can accept all pending medical records for its meet', function () {
    $meet = Meet::factory()->active()->create();
    $pending = MedicalClearance::factory()->create(['meet_id' => $meet->id, 'status' => MedicalClearanceStatus::Pending]);
    $referred = MedicalClearance::factory()->create(['meet_id' => $meet->id, 'status' => MedicalClearanceStatus::Referred]);
    $restricted = MedicalClearance::factory()->create(['meet_id' => $meet->id, 'status' => MedicalClearanceStatus::Restricted]);

    $this->actingAs(medicalTeamMember($meet))->patch('/medical-clearances/bulk-clear', [
        'all_pending' => true,
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($pending->fresh()->status)->toBe(MedicalClearanceStatus::Cleared)
        ->and($referred->fresh()->status)->toBe(MedicalClearanceStatus::Cleared)
        ->and($restricted->fresh()->status)->toBe(MedicalClearanceStatus::Restricted);
});

test('organizer cannot use one-click or bulk medical acceptance', function () {
    $clearance = MedicalClearance::factory()->create(['status' => MedicalClearanceStatus::Pending]);
    $organizer = User::factory()->organizer()->create();

    $this->actingAs($organizer)->patch("/medical-clearances/{$clearance->id}/clear")->assertForbidden();
    $this->actingAs($organizer)->patch('/medical-clearances/bulk-clear', [
        'clearance_ids' => [$clearance->id],
    ])->assertForbidden();

    expect($clearance->fresh()->status)->toBe(MedicalClearanceStatus::Pending);
});

// --- Break-glass emergency access ---

test('a coach who is not on the medical team can invoke emergency access, logged with a reason', function () {
    $clearance = MedicalClearance::factory()->create(['conditions' => 'Asthma']);
    $coach = User::factory()->coach()->create();

    $this->actingAs($coach)
        ->post('/medical-access', [
            'medical_clearance_id' => $clearance->id,
            'reason' => 'Athlete collapsed during warmups.',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('medical_access_logs', [
        'medical_clearance_id' => $clearance->id,
        'accessed_by_user_id' => $coach->id,
        'reason' => 'Athlete collapsed during warmups.',
    ]);
    expect(AuditLog::query()->where('action', 'medical_access.requested')->exists())->toBeTrue();
});

test('a viewer cannot invoke emergency access', function () {
    $clearance = MedicalClearance::factory()->create();

    $this->actingAs(User::factory()->create())
        ->post('/medical-access', [
            'medical_clearance_id' => $clearance->id,
            'reason' => 'Test.',
        ])
        ->assertForbidden();
});

test('emergency access requires a reason', function () {
    $clearance = MedicalClearance::factory()->create();

    $this->actingAs(User::factory()->coach()->create())
        ->post('/medical-access', [
            'medical_clearance_id' => $clearance->id,
            'reason' => '',
        ])
        ->assertSessionHasErrors('reason');
});

test('an admin can review a pending emergency access log', function () {
    $log = MedicalAccessLog::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/medical-access/{$log->id}/review", ['review_notes' => 'Confirmed legitimate emergency.'])
        ->assertSessionHasNoErrors();

    expect($log->fresh())
        ->reviewed_at->not->toBeNull()
        ->review_notes->toBe('Confirmed legitimate emergency.');
    expect(AuditLog::query()->where('action', 'medical_access.reviewed')->exists())->toBeTrue();
});

test('a non-lead medical team member cannot review an access log', function () {
    $meet = Meet::factory()->create();
    $clearance = MedicalClearance::factory()->create(['meet_id' => $meet->id]);
    $log = MedicalAccessLog::factory()->create(['medical_clearance_id' => $clearance->id]);

    $this->actingAs(medicalTeamMember($meet, isHead: false))
        ->patch("/medical-access/{$log->id}/review", [])
        ->assertForbidden();
});

test('a medical team lead can review an access log', function () {
    $meet = Meet::factory()->create();
    $clearance = MedicalClearance::factory()->create(['meet_id' => $meet->id]);
    $log = MedicalAccessLog::factory()->create(['medical_clearance_id' => $clearance->id]);

    $this->actingAs(medicalTeamMember($meet, isHead: true))
        ->patch("/medical-access/{$log->id}/review", [])
        ->assertSessionHasNoErrors();

    expect($log->fresh()->reviewed_at)->not->toBeNull();
});
