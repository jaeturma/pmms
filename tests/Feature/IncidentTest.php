<?php

use App\Enums\IncidentStatus;
use App\Models\AuditLog;
use App\Models\Incident;
use App\Models\Meet;
use App\Models\User;
use App\Models\Venue;
use Inertia\Testing\AssertableInertia;

test('the incident log is manager-only', function () {
    $this->get('/incidents')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/incidents')
        ->assertForbidden();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get('/incidents')
        ->assertForbidden();

    Incident::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/incidents')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('incidents/index')
            ->has('incidents.data', 1));
});

test('managers can log an incident with a venue and medical referral flag', function () {
    $meet = Meet::factory()->active()->create();
    $venue = Venue::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/incidents', [
            'meet_id' => $meet->id,
            'venue_id' => $venue->id,
            'description' => 'Athlete referred to first-aid station.',
            'severity' => 'moderate',
            'medical_referral' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $incident = Incident::query()->firstOrFail();

    expect($incident->medical_referral)->toBeTrue()
        ->and($incident->status)->toBe(IncidentStatus::Open)
        ->and($incident->reported_by)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'incident.reported')->exists())->toBeTrue();
});

test('severity must be a known level', function () {
    $meet = Meet::factory()->active()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/incidents', [
            'meet_id' => $meet->id,
            'description' => 'Something happened.',
            'severity' => 'catastrophic',
        ])
        ->assertSessionHasErrors('severity');
});

test('incidents can be updated, resolved, reopened, and deleted with audits', function () {
    $incident = Incident::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/incidents/{$incident->id}", [
            'meet_id' => $incident->meet_id,
            'venue_id' => null,
            'description' => 'Updated description.',
            'severity' => 'serious',
            'medical_referral' => false,
        ])
        ->assertSessionHasNoErrors();

    expect($incident->refresh()->description)->toBe('Updated description.')
        ->and(AuditLog::query()->where('action', 'incident.updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/incidents/{$incident->id}/resolve")
        ->assertRedirect();

    expect($incident->refresh()->status)->toBe(IncidentStatus::Resolved)
        ->and($incident->resolved_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'incident.resolved')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/incidents/{$incident->id}/reopen")
        ->assertRedirect();

    expect($incident->refresh()->status)->toBe(IncidentStatus::Open)
        ->and($incident->resolved_at)->toBeNull();

    $this->actingAs($admin)
        ->delete("/incidents/{$incident->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('incidents', ['id' => $incident->id]);

    expect(AuditLog::query()->where('action', 'incident.deleted')->exists())->toBeTrue();
});

test('the incident list filters by status', function () {
    Incident::factory()->create();
    $resolved = Incident::factory()->resolved()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/incidents?status=resolved')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('incidents.data', 1)
            ->where('incidents.data.0.id', $resolved->id));
});
