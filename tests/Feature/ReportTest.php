<?php

use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function reportOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

test('guests are redirected from report pages', function () {
    $delegation = Delegation::factory()->create();

    $this->get("/reports/delegations/{$delegation->id}/roster")->assertRedirect('/login');
    $this->get('/reports/participation')->assertRedirect('/login');
});

test('the delegation roster lists athletes and personnel', function () {
    $delegation = Delegation::factory()->create();
    Athlete::factory()->count(2)->for($delegation)->create();
    Personnel::factory()->for($delegation)->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->get("/reports/delegations/{$delegation->id}/roster")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/delegation-roster')
            ->has('athletes', 2)
            ->has('personnel', 1)
            ->where('delegation.school', $delegation->school->name));
});

test('roster access is limited to managers and assigned officers', function () {
    $delegation = Delegation::factory()->create();
    $officer = reportOfficerFor($delegation);

    $this->actingAs($officer)
        ->get("/reports/delegations/{$delegation->id}/roster")
        ->assertOk();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/reports/delegations/{$delegation->id}/roster")
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get("/reports/delegations/{$delegation->id}/roster")
        ->assertForbidden();
});

test('the roster csv downloads and is audited as a sensitive export', function () {
    $delegation = Delegation::factory()->create();
    Athlete::factory()->for($delegation)->create(['last_name' => 'Cordero']);
    $officer = reportOfficerFor($delegation);

    $response = $this->actingAs($officer)
        ->get("/reports/delegations/{$delegation->id}/roster/download");

    $response->assertOk();

    expect($response->streamedContent())->toContain('Cordero')
        ->and(AuditLog::query()->where('action', 'report.roster_exported')->count())->toBe(1);
});

test('foreign officers and viewers cannot export a roster', function () {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/reports/delegations/{$delegation->id}/roster/download")
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get("/reports/delegations/{$delegation->id}/roster/download")
        ->assertForbidden();

    expect(AuditLog::query()->where('action', 'report.roster_exported')->exists())->toBeFalse();
});

test('the event entry list is officer-scoped and excludes withdrawn entries', function () {
    $event = Event::factory()->create();
    $mine = Entry::factory()->create(['event_id' => $event->id]);
    Entry::factory()->create(['event_id' => $event->id]);
    Entry::factory()->withdrawn()->create(['event_id' => $event->id]);
    $officer = reportOfficerFor($mine->delegation);

    $this->actingAs(User::factory()->admin()->create())
        ->get("/reports/events/{$event->id}/entries")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/event-entries')
            ->has('entries', 2));

    $this->actingAs($officer)
        ->get("/reports/events/{$event->id}/entries")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries', 1)
            ->where('entries.0.id', $mine->id));

    $this->actingAs(User::factory()->create())
        ->get("/reports/events/{$event->id}/entries")
        ->assertForbidden();
});

test('the event entry csv is officer-scoped and audited', function () {
    $event = Event::factory()->create();
    $mine = Entry::factory()->create(['event_id' => $event->id]);
    $mine->athlete->update(['last_name' => 'Villanueva']);
    $other = Entry::factory()->create(['event_id' => $event->id]);
    $other->athlete->update(['last_name' => 'Zabala']);
    $officer = reportOfficerFor($mine->delegation);

    $response = $this->actingAs($officer)
        ->get("/reports/events/{$event->id}/entries/download");

    $response->assertOk();

    expect($response->streamedContent())->toContain('Villanueva')
        ->not->toContain('Zabala')
        ->and(AuditLog::query()->where('action', 'report.event_entries_exported')->count())->toBe(1);
});

test('the participation summary counts registrations per school', function () {
    $delegation = Delegation::factory()->create();
    $athletes = Athlete::factory()->count(3)->for($delegation)->create();
    Personnel::factory()->count(2)->for($delegation)->create();
    Entry::factory()->create(['athlete_id' => $athletes->first()->id]);
    School::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get('/reports/participation')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('reports/school-participation')
            ->has('rows', 1)
            ->where('rows.0.school', $delegation->school->name)
            ->where('rows.0.delegations_count', 1)
            ->where('rows.0.athletes_count', 3)
            ->where('rows.0.personnel_count', 2)
            ->where('rows.0.entries_count', 1));
});

test('the participation summary can be filtered by meet', function () {
    $meetA = Meet::factory()->create();
    $meetB = Meet::factory()->create();
    $inMeetA = Delegation::factory()->create(['meet_id' => $meetA->id]);
    Delegation::factory()->create(['meet_id' => $meetB->id]);
    Athlete::factory()->for($inMeetA)->create();

    $this->actingAs(User::factory()->create())
        ->get("/reports/participation?meet_id={$meetA->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('rows', 1)
            ->where('rows.0.school', $inMeetA->school->name)
            ->where('rows.0.athletes_count', 1));
});

test('the participation csv downloads and is audited', function () {
    $delegation = Delegation::factory()->create();

    $response = $this->actingAs(User::factory()->create())
        ->get('/reports/participation/download');

    $response->assertOk();

    expect($response->streamedContent())->toContain($delegation->school->name)
        ->and(AuditLog::query()->where('action', 'report.participation_exported')->count())->toBe(1);
});
