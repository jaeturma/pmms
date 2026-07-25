<?php

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Meet;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('the announcement registry is manager-only', function () {
    $this->get('/announcements')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/announcements')
        ->assertForbidden();

    Announcement::factory()->create();

    $this->actingAs(User::factory()->organizer()->create())
        ->get('/announcements')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('announcements/index')
            ->has('announcements.data', 1));
});

test('managers can create, update, and delete announcements with audits', function () {
    $admin = User::factory()->admin()->create();
    $meet = Meet::factory()->create();

    $this->actingAs($admin)
        ->post('/announcements', [
            'meet_id' => $meet->id,
            'title' => 'Opening ceremony moved',
            'body' => 'The opening ceremony now starts at 7:00 AM.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $announcement = Announcement::query()->firstOrFail();

    expect($announcement->is_published)->toBeFalse()
        ->and($announcement->created_by)->toBe($admin->id)
        ->and(AuditLog::query()->where('action', 'announcement.created')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->put("/announcements/{$announcement->id}", [
            'meet_id' => null,
            'title' => 'Opening ceremony moved again',
            'body' => 'Now 7:30 AM.',
        ])
        ->assertSessionHasNoErrors();

    expect($announcement->refresh()->title)->toBe('Opening ceremony moved again')
        ->and($announcement->meet_id)->toBeNull()
        ->and(AuditLog::query()->where('action', 'announcement.updated')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->delete("/announcements/{$announcement->id}")
        ->assertRedirect();

    $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);

    expect(AuditLog::query()->where('action', 'announcement.deleted')->exists())->toBeTrue();
});

test('publishing sets the timestamp and unpublishing clears it, audited', function () {
    $announcement = Announcement::factory()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/announcements/{$announcement->id}/publish")
        ->assertRedirect();

    $announcement->refresh();

    expect($announcement->is_published)->toBeTrue()
        ->and($announcement->published_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'announcement.published')->exists())->toBeTrue();

    $this->actingAs($admin)
        ->patch("/announcements/{$announcement->id}/unpublish")
        ->assertRedirect();

    $announcement->refresh();

    expect($announcement->is_published)->toBeFalse()
        ->and($announcement->published_at)->toBeNull()
        ->and(AuditLog::query()->where('action', 'announcement.unpublished')->exists())->toBeTrue();
});

test('the registry can be searched by title', function () {
    Announcement::factory()->create(['title' => 'Weather advisory']);
    Announcement::factory()->create(['title' => 'Parking notice']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/announcements?search=Weather')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements.data', 1)
            ->where('announcements.data.0.title', 'Weather advisory'));
});

test('the portal home shows published announcements newest first', function () {
    Announcement::factory()->create(['title' => 'Hidden draft']);
    Announcement::factory()->published()->create([
        'title' => 'Older news',
        'published_at' => now()->subHour(),
    ]);
    $meet = Meet::factory()->active()->published()->create();
    Announcement::factory()->published()->create([
        'title' => 'Latest news',
        'meet_id' => $meet->id,
    ]);

    $this->get('/')
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements', 2)
            ->where('announcements.0.title', 'Latest news')
            ->where('announcements.0.meet', $meet->name)
            ->where('announcements.1.title', 'Older news'));
});

test('a public meet page shows only its own published announcements', function () {
    $meet = Meet::factory()->active()->published()->create();

    Announcement::factory()->published()->create([
        'title' => 'For this meet',
        'meet_id' => $meet->id,
    ]);
    Announcement::factory()->create([
        'title' => 'Draft for this meet',
        'meet_id' => $meet->id,
    ]);
    Announcement::factory()->published()->create(['title' => 'General news']);
    Announcement::factory()->published()->create([
        'title' => 'Other meet news',
        'meet_id' => Meet::factory()->active()->published()->create()->id,
    ]);

    $this->get("/meets/{$meet->id}")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements', 1)
            ->where('announcements.0.title', 'For this meet'));
});
