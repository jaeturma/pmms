<?php

use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\Personnel;
use App\Models\Sport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

function personnelOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

function validPersonnelPayload(Delegation $delegation): array
{
    return [
        'delegation_id' => $delegation->id,
        'first_name' => 'Pedro',
        'last_name' => 'Santos',
        'role' => 'coach',
        'phone' => '09171234567',
        'email' => 'coach@example.com',
    ];
}

test('guests are redirected from the personnel registry', function () {
    $this->get('/personnel')->assertRedirect('/login');
});

test('viewers have no access to personnel', function () {
    $this->actingAs(User::factory()->create())
        ->get('/personnel')
        ->assertForbidden();
});

test('officers see only their own personnel while managers see all', function () {
    $mine = Delegation::factory()->create();
    $officer = personnelOfficerFor($mine);
    Personnel::factory()->create(['delegation_id' => $mine->id]);
    Personnel::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/personnel')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('personnel/index')
            ->has('personnel.data', 2));

    $this->actingAs($officer)
        ->get('/personnel')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('personnel.data', 1));
});

test('the registry can be searched by name', function () {
    Personnel::factory()->create(['first_name' => 'Pedro', 'last_name' => 'Santos']);
    Personnel::factory()->create(['first_name' => 'Liza', 'last_name' => 'Cruz']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/personnel?search=Santos')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('personnel.data', 1)
            ->where('personnel.data.0.name', 'Pedro Santos'));
});

test('an officer can register personnel for their open draft delegation', function () {
    $delegation = Delegation::factory()->create();
    $officer = personnelOfficerFor($delegation);

    $this->actingAs($officer)
        ->post('/personnel', validPersonnelPayload($delegation))
        ->assertRedirect();

    $this->assertDatabaseHas('personnel', ['last_name' => 'Santos']);

    expect(AuditLog::query()->where('action', 'personnel.created')->exists())->toBeTrue();
});

test('officers cannot register personnel for closed or foreign delegations', function (callable $setup) {
    [$delegation, $officer] = $setup();

    $this->actingAs($officer)
        ->post('/personnel', validPersonnelPayload($delegation))
        ->assertForbidden();
})->with([
    'registration closed' => fn () => (function () {
        $delegation = Delegation::factory()->create(['meet_id' => Meet::factory()->create()]);

        return [$delegation, personnelOfficerFor($delegation)];
    })(),
    'foreign delegation' => fn () => [
        Delegation::factory()->create(),
        User::factory()->delegationOfficer()->create(),
    ],
]);

test('personnel validation rejects bad payloads', function (array $overrides, string $errorField) {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/personnel', [...validPersonnelPayload($delegation), ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'invalid role' => [['role' => 'referee'], 'role'],
    'missing first name' => [['first_name' => ''], 'first_name'],
    'bad email' => [['email' => 'not-an-email'], 'email'],
]);

test('personnel can be registered with a photo', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/personnel', [
            ...validPersonnelPayload($delegation),
            'photo' => UploadedFile::fake()->image('coach.jpg'),
        ])
        ->assertRedirect();

    $person = Personnel::query()->sole();
    $upload = FileUpload::query()->sole();

    expect($person->photo_upload_id)->toBe($upload->id);
    Storage::disk('local')->assertExists($upload->path);
});

test('sports can be assigned to a coach and the sync replaces prior sets', function () {
    $coach = Personnel::factory()->coach()->create();
    $sports = Sport::factory()->count(3)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put("/personnel/{$coach->id}/sports", ['sport_ids' => [$sports[0]->id, $sports[1]->id]])
        ->assertRedirect();

    expect($coach->sports()->count())->toBe(2)
        ->and(AuditLog::query()->where('action', 'personnel.sports_updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->put("/personnel/{$coach->id}/sports", ['sport_ids' => [$sports[2]->id]]);

    expect($coach->sports()->pluck('sports.id')->all())->toBe([$sports[2]->id]);
});

test('chaperones cannot be assigned sports', function () {
    $chaperone = Personnel::factory()->chaperone()->create();
    $sport = Sport::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/personnel/{$chaperone->id}/sports", ['sport_ids' => [$sport->id]]);

    expect($chaperone->sports()->count())->toBe(0);
});

test('sport assignment validates sport ids', function () {
    $coach = Personnel::factory()->coach()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->put("/personnel/{$coach->id}/sports", ['sport_ids' => [999999]])
        ->assertSessionHasErrors('sport_ids.0');
});

test('changing a coach to chaperone clears sport assignments', function () {
    $coach = Personnel::factory()->coach()->create();
    $coach->sports()->attach(Sport::factory()->create());

    $this->actingAs(User::factory()->admin()->create())
        ->put("/personnel/{$coach->id}", [
            'first_name' => $coach->first_name,
            'last_name' => $coach->last_name,
            'role' => 'chaperone',
            'phone' => null,
            'email' => null,
        ])
        ->assertRedirect();

    expect($coach->refresh()->sports()->count())->toBe(0);
});

test('updates and deletions are audited and clean up photos', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/personnel', [
        ...validPersonnelPayload($delegation),
        'photo' => UploadedFile::fake()->image('coach.jpg'),
    ]);

    $person = Personnel::query()->sole();
    $upload = FileUpload::query()->sole();

    $this->actingAs($admin)
        ->put("/personnel/{$person->id}", [
            'first_name' => 'Renamed',
            'last_name' => $person->last_name,
            'role' => 'coach',
            'phone' => null,
            'email' => null,
        ])
        ->assertRedirect();

    expect($person->refresh()->first_name)->toBe('Renamed')
        ->and(AuditLog::query()->where('action', 'personnel.updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete("/personnel/{$person->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('personnel', ['id' => $person->id]);
    $this->assertDatabaseMissing('file_uploads', ['id' => $upload->id]);

    expect(AuditLog::query()->where('action', 'personnel.deleted')->exists())->toBeTrue();
});

test('delegations with personnel cannot be deleted', function () {
    $person = Personnel::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/delegations/{$person->delegation_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('delegations', ['id' => $person->delegation_id]);
});
