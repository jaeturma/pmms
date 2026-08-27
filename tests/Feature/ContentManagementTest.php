<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\UserRole;
use App\Models\FaqItem;
use App\Models\FileUpload;
use App\Models\GalleryItem;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\Meet;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\NewsItem;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->withoutVite();
});

function informationTeamUser(Meet $meet): User
{
    $user = User::factory()->create(['role' => UserRole::Viewer]);
    $team = ManagementTeam::factory()->create(['meet_id' => $meet->id, 'source_code' => 'INFORMATION']);
    ManagementTeamMember::factory()->create(['management_team_id' => $team->id, 'user_id' => $user->id, 'status' => ManagementTeamMemberStatus::Active]);

    return $user;
}

test('information team can access every content management registry', function () {
    $meet = Meet::factory()->featured()->published()->create();
    $user = informationTeamUser($meet);

    $this->actingAs($user)->get('/content')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('content/index')->where('canManageEditorial', true));
    $this->actingAs($user)->get('/content/news')->assertOk();
    $this->actingAs($user)->get('/announcements')->assertOk();
    $this->actingAs($user)->get('/content/faq')->assertOk();
    $this->actingAs($user)->get('/content/gallery')->assertOk();
});

test('tournament ICT can upload only to an assigned sport and cannot publish', function () {
    Storage::fake('local');
    $meet = Meet::factory()->featured()->published()->create();
    $assigned = MeetSport::factory()->create(['meet_id' => $meet->id]);
    $unrelated = MeetSport::factory()->create(['meet_id' => $meet->id]);
    $ict = User::factory()->create(['role' => UserRole::TournamentICT]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $assigned->id, 'user_id' => $ict->id,
        'role' => MeetSportAssignmentRole::TournamentICT, 'status' => MeetSportAssignmentStatus::Active,
    ]);

    $payload = fn (MeetSport $scope) => [
        'meet_sport_id' => $scope->id, 'capture_date' => today()->toDateString(),
        'photos' => [UploadedFile::fake()->image('candidate.jpg', 800, 600)],
    ];

    $this->actingAs($ict)->post('/content/gallery', $payload($assigned))->assertRedirect();
    expect(GalleryItem::query()->sole()->status)->toBe('submitted');
    $this->actingAs($ict)->post('/content/gallery', $payload($unrelated))->assertForbidden();
    $this->actingAs($ict)->patch('/content/gallery/publish', ['ids' => [GalleryItem::query()->sole()->id]])->assertForbidden();
    $this->actingAs($ict)->get('/content/news')->assertForbidden();
    $this->actingAs($ict)->get('/content/faq')->assertForbidden();
    $this->actingAs($ict)->get('/announcements')->assertForbidden();
});

test('tournament secretary receives the same assigned gallery contribution scope', function () {
    $meetSport = MeetSport::factory()->create();
    $secretary = User::factory()->create(['role' => UserRole::TournamentSecretary]);
    MeetSportAssignment::factory()->create(['meet_sport_id' => $meetSport->id, 'user_id' => $secretary->id, 'role' => MeetSportAssignmentRole::TournamentSecretary, 'status' => MeetSportAssignmentStatus::Active]);

    $this->actingAs($secretary)->get('/content/gallery')->assertOk();
    $this->actingAs($secretary)->patch('/content/gallery/publish', ['ids' => [999]])->assertForbidden();
});

test('only published gallery items are public and publication limit is enforced per sport and day', function () {
    Storage::fake('local');
    config()->set('pmms.gallery.daily_public_max', 1);
    $meet = Meet::factory()->featured()->published()->create();
    $meetSport = MeetSport::factory()->create(['meet_id' => $meet->id]);
    $editor = informationTeamUser($meet);
    $upload = FileUpload::factory()->create(['disk' => 'local', 'path' => 'uploads/photo.jpg']);
    Storage::disk('local')->put('uploads/photo.jpg', 'image');
    $first = GalleryItem::query()->create(['meet_id' => $meet->id, 'meet_sport_id' => $meetSport->id, 'file_upload_id' => $upload->id, 'capture_date' => today(), 'status' => 'approved']);
    $second = GalleryItem::query()->create(['meet_id' => $meet->id, 'meet_sport_id' => $meetSport->id, 'file_upload_id' => $upload->id, 'capture_date' => today(), 'status' => 'approved']);

    $this->get("/gallery-images/{$first->id}")->assertNotFound();
    $this->actingAs($editor)->patch('/content/gallery/publish', ['ids' => [$first->id]])->assertRedirect();
    $this->actingAs($editor)->patch('/content/gallery/publish', ['ids' => [$second->id]])->assertSessionHasErrors('ids');
    $this->get('/gallery')->assertInertia(fn (AssertableInertia $page) => $page->has('items', 1)->where('items.0.id', $first->id));
    $this->get("/gallery-images/{$first->id}")->assertOk();
});

test('public news and FAQ expose only published records', function () {
    $meet = Meet::factory()->featured()->published()->create();
    NewsItem::query()->create(['meet_id' => $meet->id, 'title' => 'Draft', 'slug' => 'draft', 'body' => 'Hidden', 'status' => 'draft']);
    $news = NewsItem::query()->create(['meet_id' => $meet->id, 'title' => 'Published', 'slug' => 'published', 'body' => 'Visible', 'status' => 'published', 'published_at' => now()]);
    FaqItem::query()->create(['meet_id' => $meet->id, 'question' => 'Draft?', 'answer' => 'Hidden', 'status' => 'draft']);
    $faq = FaqItem::query()->create(['meet_id' => $meet->id, 'question' => 'Published?', 'answer' => 'Visible', 'status' => 'published', 'published_at' => now()]);

    $this->get('/news')->assertInertia(fn (AssertableInertia $page) => $page->has('news.data', 1)->where('news.data.0.id', $news->id));
    $this->get('/faq')->assertInertia(fn (AssertableInertia $page) => $page->has('items', 1)->where('items.0.id', $faq->id));
    $this->get('/news/published')->assertOk();
    $this->get('/news/draft')->assertNotFound();
});
