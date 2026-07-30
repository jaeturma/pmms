<?php

use App\Models\Announcement;
use App\Models\Meet;
use Inertia\Testing\AssertableInertia;

test('guests can view the news page for a published meet; unpublished meets 404', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/news")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/news')
            ->where('meet.name', $meet->name));

    $hidden = Meet::factory()->active()->create();

    $this->get("/meets/{$hidden->id}/news")->assertNotFound();
});

test('only this meet\'s published announcements appear, newest first', function () {
    $meet = Meet::factory()->active()->published()->create();
    $otherMeet = Meet::factory()->active()->published()->create();

    $older = Announcement::factory()->published()->create([
        'meet_id' => $meet->id,
        'published_at' => now()->subDay(),
    ]);
    $newer = Announcement::factory()->published()->create([
        'meet_id' => $meet->id,
        'published_at' => now(),
    ]);
    Announcement::factory()->create(['meet_id' => $meet->id]); // unpublished
    Announcement::factory()->published()->create(['meet_id' => $otherMeet->id]);
    Announcement::factory()->published()->create(['meet_id' => null]); // general, no meet

    $this->get("/meets/{$meet->id}/news")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements.data', 2)
            ->where('announcements.data.0.id', $newer->id)
            ->where('announcements.data.1.id', $older->id));
});

test('the news list is paginated past the home page\'s 5-item preview limit', function () {
    $meet = Meet::factory()->active()->published()->create();

    Announcement::factory()->published()->count(12)->create(['meet_id' => $meet->id]);

    $this->get("/meets/{$meet->id}/news")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements.data', 10)
            ->where('announcements.total', 12)
            ->where('announcements.current_page', 1)
            ->where('announcements.last_page', 2));

    $this->get("/meets/{$meet->id}/news?page=2")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements.data', 2)
            ->where('announcements.current_page', 2));
});

test('a published meet with no announcements shows the empty state props', function () {
    $meet = Meet::factory()->active()->published()->create();

    $this->get("/meets/{$meet->id}/news")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements.data', 0));
});

test('announcement rows carry no internal fields', function () {
    $meet = Meet::factory()->active()->published()->create();

    Announcement::factory()->published()->create(['meet_id' => $meet->id]);

    $this->get("/meets/{$meet->id}/news")
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('announcements.data.0', fn (AssertableInertia $row) => $row
                ->hasAll(['id', 'title', 'body', 'meet', 'published_at'])
                ->missing('is_published')
                ->missing('created_by')
                ->missing('meet_id')));
});
