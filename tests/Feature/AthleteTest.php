<?php

use App\Models\Athlete;
use App\Models\AuditLog;
use App\Models\Delegation;
use App\Models\FileUpload;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

function athleteOfficerFor(Delegation $delegation): User
{
    $officer = User::factory()->delegationOfficer()->create();
    $delegation->officers()->attach($officer);

    return $officer;
}

function validAthletePayload(Delegation $delegation): array
{
    return [
        'delegation_id' => $delegation->id,
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'sex' => 'female',
        'birthdate' => now()->subYears(12)->toDateString(),
        'lrn' => '123456789012',
        'grade_level' => 6,
    ];
}

test('guests are redirected from the athlete registry', function () {
    $this->get('/athletes')->assertRedirect('/login');
});

test('viewers have no access to athlete data', function () {
    $this->actingAs(User::factory()->create())
        ->get('/athletes')
        ->assertForbidden();
});

test('officers see only their own athletes while managers see all', function () {
    $mine = Delegation::factory()->create();
    $officer = athleteOfficerFor($mine);
    Athlete::factory()->create(['delegation_id' => $mine->id]);
    Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/athletes')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('athletes/index')
            ->has('athletes.data', 2));

    $this->actingAs($officer)
        ->get('/athletes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1));
});

test('the registry can be searched by name and lrn', function () {
    Athlete::factory()->create(['first_name' => 'Ana', 'last_name' => 'Reyes']);
    Athlete::factory()->create(['first_name' => 'Ben', 'last_name' => 'Cruz', 'lrn' => '999888777666']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/athletes?search=Reyes')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.name', 'Ana Reyes'));

    $this->actingAs($admin)
        ->get('/athletes?search=999888')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('athletes.data', 1)
            ->where('athletes.data.0.name', 'Ben Cruz'));
});

test('an officer can register an athlete for their open draft delegation', function () {
    $delegation = Delegation::factory()->create();
    $officer = athleteOfficerFor($delegation);

    $this->actingAs($officer)
        ->post('/athletes', validAthletePayload($delegation))
        ->assertRedirect();

    $this->assertDatabaseHas('athletes', ['lrn' => '123456789012']);

    expect(AuditLog::query()->where('action', 'athlete.created')->exists())->toBeTrue();
});

test('officers cannot register athletes for closed or foreign delegations', function (callable $setup) {
    [$delegation, $officer] = $setup();

    $this->actingAs($officer)
        ->post('/athletes', validAthletePayload($delegation))
        ->assertForbidden();
})->with([
    'registration closed' => fn () => (function () {
        $delegation = Delegation::factory()->create(['meet_id' => Meet::factory()->create()]);

        return [$delegation, athleteOfficerFor($delegation)];
    })(),
    'submitted delegation' => fn () => (function () {
        $delegation = Delegation::factory()->submitted()->create();

        return [$delegation, athleteOfficerFor($delegation)];
    })(),
    'foreign delegation' => fn () => [
        Delegation::factory()->create(),
        User::factory()->delegationOfficer()->create(),
    ],
]);

test('managers can register athletes regardless of the window', function () {
    $delegation = Delegation::factory()->submitted()->create(['meet_id' => Meet::factory()->create()]);

    $this->actingAs(User::factory()->organizer()->create())
        ->post('/athletes', validAthletePayload($delegation))
        ->assertRedirect();

    $this->assertDatabaseHas('athletes', ['delegation_id' => $delegation->id]);
});

test('athlete validation rejects bad payloads', function (array $overrides, string $errorField) {
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [...validAthletePayload($delegation), ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'future birthdate' => [['birthdate' => '2030-01-01'], 'birthdate'],
    'too old' => [['birthdate' => '1980-01-01'], 'birthdate'],
    'short lrn' => [['lrn' => '12345'], 'lrn'],
    'bad sex' => [['sex' => 'other'], 'sex'],
    'bad grade' => [['grade_level' => 13], 'grade_level'],
]);

test('lrn must be unique', function () {
    Athlete::factory()->create(['lrn' => '123456789012']);
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', validAthletePayload($delegation))
        ->assertSessionHasErrors('lrn');
});

test('viewing an athlete profile is audited', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('athletes/show')
            ->where('athlete.lrn', $athlete->lrn));

    expect(AuditLog::query()->where('action', 'athlete.viewed')->exists())->toBeTrue();
});

test('officers cannot view athletes of other delegations', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/athletes/{$athlete->id}")
        ->assertForbidden();
});

test('an athlete can be registered with a photo', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'photo' => UploadedFile::fake()->image('athlete.jpg'),
        ])
        ->assertRedirect();

    $athlete = Athlete::query()->sole();
    $upload = FileUpload::query()->sole();

    expect($athlete->photo_upload_id)->toBe($upload->id);
    Storage::disk('local')->assertExists($upload->path);
});

test('the athlete photo is served to authorized users only', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post('/athletes', [
            ...validAthletePayload($delegation),
            'photo' => UploadedFile::fake()->image('athlete.jpg'),
        ]);

    $athlete = Athlete::query()->sole();

    $this->actingAs(User::factory()->admin()->create())
        ->get("/athletes/{$athlete->id}/photo")
        ->assertOk();

    $this->actingAs(User::factory()->delegationOfficer()->create())
        ->get("/athletes/{$athlete->id}/photo")
        ->assertForbidden();
});

test('updates and deletions are audited and clean up photos', function () {
    Storage::fake('local');
    $delegation = Delegation::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post('/athletes', [
        ...validAthletePayload($delegation),
        'photo' => UploadedFile::fake()->image('athlete.jpg'),
    ]);

    $athlete = Athlete::query()->sole();

    $this->actingAs($admin)
        ->put("/athletes/{$athlete->id}", [
            ...validAthletePayload($delegation),
            'first_name' => 'Renamed',
        ])
        ->assertRedirect();

    expect($athlete->refresh()->first_name)->toBe('Renamed')
        ->and(AuditLog::query()->where('action', 'athlete.updated')->exists())->toBeTrue();

    $upload = FileUpload::query()->sole();

    $this->actingAs($admin)
        ->delete("/athletes/{$athlete->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('athletes', ['id' => $athlete->id]);
    $this->assertDatabaseMissing('file_uploads', ['id' => $upload->id]);

    expect(AuditLog::query()->where('action', 'athlete.deleted')->exists())->toBeTrue();
});

test('delegations with athletes cannot be deleted', function () {
    $athlete = Athlete::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->delete("/delegations/{$athlete->delegation_id}")
        ->assertRedirect();

    $this->assertDatabaseHas('delegations', ['id' => $athlete->delegation_id]);
});
