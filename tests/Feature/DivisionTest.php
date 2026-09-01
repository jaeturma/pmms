<?php

use App\Enums\DivisionType;
use App\Models\AuditLog;
use App\Models\CongressionalDistrict;
use App\Models\Delegation;
use App\Models\District;
use App\Models\Division;
use App\Models\FileUpload;
use App\Models\User;
use Database\Seeders\DivisionRegistrySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

test('Division::current creates a Province default on first access', function () {
    expect(Division::query()->count())->toBe(0);

    $division = Division::current();

    expect($division->type)->toBe(DivisionType::Province)
        ->and($division->areaLabel())->toBe('Municipality')
        ->and(Division::query()->count())->toBe(1);

    expect(Division::current()->id)->toBe($division->id);
});

test('the division is shared on every Inertia page with its area label', function () {
    Division::factory()->province()->create(['name' => 'Davao de Oro']);

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('division.type', 'province')
            ->where('division.name', 'Davao de Oro')
            ->where('division.areaLabel', 'Municipality'));
});

test('the division type is locked once any delegation exists', function () {
    expect(Division::current()->typeIsLocked())->toBeFalse();

    Delegation::factory()->create();

    expect(Division::current()->typeIsLocked())->toBeTrue();
});

test('guests are redirected from division settings', function () {
    $this->get('/division')->assertRedirect('/login');
});

test('only admins can view or update division settings', function (User $user) {
    $this->actingAs($user)
        ->get('/division')
        ->assertForbidden();

    $this->actingAs($user)
        ->patch('/division', ['name' => 'X', 'type' => 'city'])
        ->assertForbidden();
})->with([
    'organizer' => fn () => User::factory()->organizer()->create(),
    'delegation officer' => fn () => User::factory()->delegationOfficer()->create(),
    'viewer' => fn () => User::factory()->create(),
]);

test('admins can view division settings with the lock state', function () {
    Division::factory()->province()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get('/division')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('division/edit')
            ->where('division.type', 'province')
            ->where('typeLocked', false));
});

test('admins can update the division name and type when unlocked, audited', function () {
    Division::factory()->province()->create(['name' => 'Old Name']);

    $this->actingAs(User::factory()->admin()->create())
        ->patch('/division', ['name' => 'New Name', 'type' => 'city'])
        ->assertRedirect();

    $division = Division::current();

    expect($division->name)->toBe('New Name')
        ->and($division->type)->toBe(DivisionType::City)
        ->and(AuditLog::query()->where('action', 'division.updated')->exists())->toBeTrue();
});

test('the division type cannot be changed once locked, even if submitted', function () {
    Division::factory()->province()->create();
    Delegation::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch('/division', ['name' => 'Still Province', 'type' => 'city'])
        ->assertRedirect();

    expect(Division::current()->type)->toBe(DivisionType::Province)
        ->and(Division::current()->name)->toBe('Still Province');
});

test('the division registry seeder creates the real default configuration', function () {
    (new DivisionRegistrySeeder)->run();

    $division = Division::current();

    expect($division->type)->toBe(DivisionType::Province)
        ->and($division->name)->toBe('Davao de Oro')
        ->and(District::query()->count())->toBe(11);

    $maco = District::query()->where('name', 'Maco')->firstOrFail();
    expect($maco->nickname)->toBe('Power Voltz')
        ->and($maco->congressional_district)->toBe('Second')
        ->and($maco->congressionalDistrict->name)->toBe('Second');

    expect(CongressionalDistrict::query()->count())->toBe(2);

    // Idempotent: re-running does not duplicate rows.
    (new DivisionRegistrySeeder)->run();
    expect(Division::query()->count())->toBe(1)
        ->and(District::query()->count())->toBe(11)
        ->and(CongressionalDistrict::query()->count())->toBe(2);
});

test('admins can upload a public landing hero icon, served publicly', function () {
    Storage::fake('local');
    Division::factory()->province()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->patch('/division', [
            'name' => 'Davao de Oro',
            'type' => 'province',
            'hero_icon' => UploadedFile::fake()->image('hero.png'),
        ])
        ->assertRedirect();

    $division = Division::current();
    $upload = FileUpload::query()->sole();

    expect($division->hero_icon_upload_id)->toBe($upload->id);
    Storage::disk('local')->assertExists($upload->path);

    // Public — no authentication required, same as the site logo.
    $this->get('/division/hero-icon')->assertOk();
});

test('the hero icon route 404s until one is uploaded', function () {
    Division::factory()->province()->create();

    $this->get('/division/hero-icon')->assertNotFound();
});

test('replacing the hero icon deletes the old upload', function () {
    Storage::fake('local');
    Division::factory()->province()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch('/division', [
        'name' => 'Davao de Oro',
        'type' => 'province',
        'hero_icon' => UploadedFile::fake()->image('first.png'),
    ]);

    $firstUpload = Division::current()->heroIcon;

    $this->actingAs($admin)->patch('/division', [
        'name' => 'Davao de Oro',
        'type' => 'province',
        'hero_icon' => UploadedFile::fake()->image('second.png'),
    ]);

    $division = Division::current();

    expect($division->hero_icon_upload_id)->not->toBe($firstUpload->id)
        ->and(FileUpload::query()->whereKey($firstUpload->id)->exists())->toBeFalse();
    Storage::disk('local')->assertExists($division->heroIcon->path);
});

test('admins can remove the hero icon back to the default mark', function () {
    Storage::fake('local');
    Division::factory()->province()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch('/division', [
        'name' => 'Davao de Oro',
        'type' => 'province',
        'hero_icon' => UploadedFile::fake()->image('hero.png'),
    ]);

    $upload = Division::current()->heroIcon;

    $this->actingAs($admin)
        ->patch('/division', ['name' => 'Davao de Oro', 'type' => 'province', 'remove_hero_icon' => true])
        ->assertRedirect();

    expect(Division::current()->hero_icon_upload_id)->toBeNull()
        ->and(FileUpload::query()->whereKey($upload->id)->exists())->toBeFalse();

    $this->get('/division/hero-icon')->assertNotFound();
});

test('the hero logo url is shared on every page once uploaded, null otherwise', function () {
    Storage::fake('local');
    Division::factory()->province()->create();

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('division.heroLogoUrl', null));

    $this->actingAs(User::factory()->admin()->create())->patch('/division', [
        'name' => 'Davao de Oro',
        'type' => 'province',
        'hero_icon' => UploadedFile::fake()->image('hero.png'),
    ]);

    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertInertia(fn (AssertableInertia $page) => $page->where('division.heroLogoUrl', route('division.hero-icon')));
});
