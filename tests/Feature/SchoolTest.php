<?php

use App\Enums\SchoolLevel;
use App\Models\AuditLog;
use App\Models\District;
use App\Models\School;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

function validSchoolPayload(District $district): array
{
    return [
        'district_id' => $district->id,
        'name' => 'Poblacion Elementary School',
        'school_id_code' => '123456',
        'level' => SchoolLevel::Elementary->value,
        'address' => 'Poblacion, Sample Town',
    ];
}

test('guests are redirected from the school registry', function () {
    $this->get('/schools')->assertRedirect('/login');
});

test('the school registry renders with schools and district options', function () {
    School::factory()->create();
    District::factory()->archived()->create();

    $this->actingAs(User::factory()->create())
        ->get('/schools')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('registry/schools')
            ->has('schools.data', 1)
            ->has('districts', 1)
            ->where('canManage', false));
});

test('the school registry can be searched by name, code, and district', function () {
    $north = District::factory()->create(['name' => 'North Cluster']);
    $south = District::factory()->create(['name' => 'South Cluster']);
    School::factory()->create([
        'district_id' => $north->id,
        'name' => 'Mabini Elementary School',
        'school_id_code' => '111111',
    ]);
    School::factory()->create([
        'district_id' => $south->id,
        'name' => 'Rizal High School',
        'school_id_code' => '222222',
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/schools?search=Mabini')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools.data', 1)
            ->where('schools.data.0.name', 'Mabini Elementary School'));

    $this->actingAs($admin)
        ->get('/schools?search=222222')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools.data', 1)
            ->where('schools.data.0.name', 'Rizal High School'));

    $this->actingAs($admin)
        ->get('/schools?search=North Cluster')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools.data', 1)
            ->where('schools.data.0.name', 'Mabini Elementary School'));
});

test('the school registry paginates fifteen rows per page', function () {
    School::factory()->count(20)->create();

    $viewer = User::factory()->create();

    $this->actingAs($viewer)
        ->get('/schools')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools.data', 15)
            ->where('schools.total', 20)
            ->where('schools.current_page', 1)
            ->where('schools.last_page', 2));

    $this->actingAs($viewer)
        ->get('/schools?page=2')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('schools.data', 5)
            ->where('schools.current_page', 2));
});

test('organizers can create schools', function () {
    $district = District::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/schools', validSchoolPayload($district))
        ->assertRedirect();

    $this->assertDatabaseHas('schools', [
        'name' => 'Poblacion Elementary School',
        'district_id' => $district->id,
    ]);

    expect(AuditLog::query()->where('action', 'school.created')->exists())->toBeTrue();
});

test('viewers and delegation officers cannot create schools', function (User $user) {
    $district = District::factory()->create();

    $this->actingAs($user)
        ->post('/schools', validSchoolPayload($district))
        ->assertForbidden();
})->with([
    'viewer' => fn () => User::factory()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
]);

test('school validation rejects bad payloads', function (array $overrides, string $errorField) {
    $district = District::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schools', [...validSchoolPayload($district), ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'missing district' => [['district_id' => 999999], 'district_id'],
    'invalid level' => [['level' => 'college'], 'level'],
    'missing name' => [['name' => ''], 'name'],
    'missing code' => [['school_id_code' => ''], 'school_id_code'],
]);

test('school id codes must be unique', function () {
    $district = District::factory()->create();
    School::factory()->create(['school_id_code' => '123456']);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schools', validSchoolPayload($district))
        ->assertSessionHasErrors('school_id_code');
});

test('school names must be unique within a district but not across districts', function () {
    $district = District::factory()->create();
    $other = District::factory()->create();
    School::factory()->create(['district_id' => $district->id, 'name' => 'Poblacion Elementary School']);

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schools', validSchoolPayload($district))
        ->assertSessionHasErrors('name');

    $this->actingAs(User::factory()->admin()->create())
        ->post('/schools', [...validSchoolPayload($other), 'school_id_code' => '654321'])
        ->assertRedirect()
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('schools', [
        'name' => 'Poblacion Elementary School',
        'district_id' => $other->id,
    ]);
});

test('admins can update a school', function () {
    $school = School::factory()->create();

    $payload = [
        'district_id' => $school->district_id,
        'name' => 'Renamed Integrated School',
        'school_id_code' => $school->school_id_code,
        'level' => SchoolLevel::Integrated->value,
        'address' => null,
    ];

    $this->actingAs(User::factory()->admin()->create())
        ->put("/schools/{$school->id}", $payload)
        ->assertRedirect();

    expect($school->refresh())
        ->name->toBe('Renamed Integrated School')
        ->level->toBe(SchoolLevel::Integrated)
        ->and(AuditLog::query()->where('action', 'school.updated')->exists())->toBeTrue();
});

test('archiving and restoring a school toggles active', function () {
    $school = School::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/schools/{$school->id}/archive")
        ->assertRedirect();

    expect($school->refresh()->active)->toBeFalse()
        ->and(AuditLog::query()->where('action', 'school.archived')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/schools/{$school->id}/restore")
        ->assertRedirect();

    expect($school->refresh()->active)->toBeTrue();
});

test('schools can be deleted and the deletion is audited', function () {
    $school = School::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/schools/{$school->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);

    expect(AuditLog::query()->where('action', 'school.deleted')->exists())->toBeTrue();
});
