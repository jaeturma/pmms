<?php

use App\Enums\ProtestStatus;
use App\Enums\ResultStatus;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\EventMatch;
use App\Models\EventResult;
use App\Models\Protest;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('guests are redirected and viewers are forbidden from protests', function () {
    $this->get('/protests')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/protests')
        ->assertForbidden();
});

test('officers see only their own delegation\'s protests; managers all', function () {
    $mine = Protest::factory()->create();
    Protest::factory()->create();

    $officer = User::factory()->delegationOfficer()->create();
    $mine->delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->get('/protests')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('protests/index')
            ->has('protests.data', 1)
            ->where('protests.data.0.id', $mine->id)
            ->where('canManage', false));

    $this->actingAs(User::factory()->admin()->create())
        ->get('/protests')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('protests.data', 2)
            ->where('canManage', true));
});

test('officers can file for their own delegation only; managers for any', function () {
    $delegation = Delegation::factory()->approved()->create();
    $result = EventResult::factory()->validated()->create(['meet_id' => $delegation->meet_id]);

    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $result->id,
            'grounds' => 'Lane obstruction was ignored.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Protest::query()->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'protest.filed')->exists())->toBeTrue();

    $stranger = User::factory()->delegationOfficer()->create();

    $this->actingAs($stranger)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $result->id,
            'grounds' => 'Trying to file for someone else.',
        ])
        ->assertForbidden();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $result->id,
            'grounds' => 'Manager-filed protest.',
        ])
        ->assertSessionHasNoErrors();

    expect(Protest::query()->count())->toBe(2);
});

test('a protest targets exactly one result or match of its own meet', function () {
    $delegation = Delegation::factory()->approved()->create();
    $result = EventResult::factory()->validated()->create(['meet_id' => $delegation->meet_id]);
    $match = EventMatch::factory()->create(['meet_id' => $delegation->meet_id]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'grounds' => 'No target given.',
        ])
        ->assertSessionHasErrors('event_result_id');

    $this->actingAs($admin)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $result->id,
            'match_id' => $match->id,
            'grounds' => 'Two targets given.',
        ])
        ->assertSessionHasErrors();

    $foreignResult = EventResult::factory()->validated()->create();

    $this->actingAs($admin)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $foreignResult->id,
            'grounds' => 'Result from another meet.',
        ])
        ->assertSessionHasErrors('event_result_id');

    $this->actingAs($admin)
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'match_id' => $match->id,
            'grounds' => 'Valid match protest.',
        ])
        ->assertSessionHasNoErrors();

    expect(Protest::query()->count())->toBe(1);
});

test('filed protests go under review before a decision', function () {
    $protest = Protest::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/protests/{$protest->id}/decide", [
            'decision' => 'upheld',
            'remarks' => 'Too early.',
        ])
        ->assertRedirect();

    expect($protest->refresh()->status)->toBe(ProtestStatus::Filed);

    $this->actingAs($admin)
        ->patch("/protests/{$protest->id}/review")
        ->assertRedirect();

    expect($protest->refresh()->status)->toBe(ProtestStatus::UnderReview)
        ->and(AuditLog::query()->where('action', 'protest.under_review')->exists())->toBeTrue();
});

test('decisions require remarks and record the decider', function () {
    $protest = Protest::factory()->underReview()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/protests/{$protest->id}/decide", [
            'decision' => 'upheld',
            'remarks' => '',
        ])
        ->assertSessionHasErrors('remarks');

    $this->actingAs($admin)
        ->patch("/protests/{$protest->id}/decide", [
            'decision' => 'upheld',
            'remarks' => 'Photo finish supports the protest.',
        ])
        ->assertSessionHasNoErrors();

    $protest->refresh();

    expect($protest->status)->toBe(ProtestStatus::Upheld)
        ->and($protest->decided_by)->toBe($admin->id)
        ->and($protest->decided_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'protest.upheld')->exists())->toBeTrue();
});

test('decided protests are terminal', function () {
    $protest = Protest::factory()->dismissed()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/protests/{$protest->id}/decide", [
            'decision' => 'upheld',
            'remarks' => 'Changing my mind.',
        ])
        ->assertRedirect();

    expect($protest->refresh()->status)->toBe(ProtestStatus::Dismissed);
});

test('upheld result protests expose the correction link and the flow works end-to-end', function () {
    $protest = Protest::factory()->upheld()->create();
    $result = $protest->result;

    $this->actingAs(User::factory()->admin()->create())
        ->get('/protests')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('protests.data.0.correctable_result_id', $result->id)
            ->where(
                'protests.data.0.correction_reason',
                "Protest #{$protest->id} upheld: {$protest->remarks}",
            ));

    $this->actingAs(User::factory()->admin()->create())
        ->patch("/results/{$result->id}/correct", [
            'reason' => "Protest #{$protest->id} upheld: {$protest->remarks}",
        ])
        ->assertSessionHasNoErrors();

    expect($result->refresh()->status)->toBe(ResultStatus::Encoded)
        ->and(AuditLog::query()->where('action', 'result.corrected')->exists())->toBeTrue();
});

test('viewers cannot file protests at all', function () {
    $delegation = Delegation::factory()->approved()->create();
    $result = EventResult::factory()->validated()->create(['meet_id' => $delegation->meet_id]);

    $this->actingAs(User::factory()->create())
        ->post('/protests', [
            'delegation_id' => $delegation->id,
            'event_result_id' => $result->id,
            'grounds' => 'Viewer attempting to file.',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('protests', 0);
});

test('officers cannot review or decide protests', function () {
    $protest = Protest::factory()->create();
    $officer = User::factory()->delegationOfficer()->create();
    $protest->delegation->officers()->attach($officer);

    $this->actingAs($officer)
        ->patch("/protests/{$protest->id}/review")
        ->assertForbidden();

    $this->actingAs($officer)
        ->patch("/protests/{$protest->id}/decide", [
            'decision' => 'upheld',
            'remarks' => 'Officer deciding own protest.',
        ])
        ->assertForbidden();
});
