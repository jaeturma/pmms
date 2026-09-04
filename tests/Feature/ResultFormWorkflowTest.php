<?php

use App\Enums\ManagementTeamMemberStatus;
use App\Enums\ManagementTeamStatus;
use App\Enums\ManagementTeamType;
use App\Enums\MeetSportAssignmentRole;
use App\Enums\MeetSportAssignmentStatus;
use App\Enums\ResultStatus;
use App\Models\AuditLog;
use App\Models\EventResult;
use App\Models\ManagementTeam;
use App\Models\ManagementTeamMember;
use App\Models\MeetSport;
use App\Models\MeetSportAssignment;
use App\Models\ResultAttachment;
use App\Models\ResultPlacement;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function assignedResultUser(EventResult $result, MeetSportAssignmentRole $role): User
{
    $user = User::factory()->technicalOfficial()->create();
    $meetSport = MeetSport::factory()->create([
        'meet_id' => $result->meet_id,
        'sport_id' => $result->event->sport_id,
    ]);
    MeetSportAssignment::factory()->create([
        'meet_sport_id' => $meetSport->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => MeetSportAssignmentStatus::Active,
    ]);

    return $user;
}

function eventSecretariatFor(EventResult $result): User
{
    $user = User::factory()->create();
    $team = ManagementTeam::factory()->create([
        'meet_id' => $result->meet_id,
        'team_type' => ManagementTeamType::MeetManagement,
        'source_code' => 'EVENT_SECRETARIAT',
        'status' => ManagementTeamStatus::Active,
    ]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'user_id' => $user->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $user;
}

function topManagementFor(EventResult $result): User
{
    $user = User::factory()->create();
    $team = ManagementTeam::factory()->create([
        'meet_id' => $result->meet_id,
        'team_type' => ManagementTeamType::TopManagement,
        'source_code' => 'TOP_MANAGEMENT',
        'status' => ManagementTeamStatus::Active,
    ]);
    ManagementTeamMember::factory()->create([
        'management_team_id' => $team->id,
        'user_id' => $user->id,
        'status' => ManagementTeamMemberStatus::Active,
    ]);

    return $user;
}

function resultWithPlacement(): EventResult
{
    $result = EventResult::factory()->create();
    ResultPlacement::factory()->create(['event_result_id' => $result->id]);

    return $result;
}

test('authorized assigned sport personnel can generate their result form without changing status', function (MeetSportAssignmentRole $role) {
    $result = resultWithPlacement();
    $user = assignedResultUser($result, $role);

    $this->actingAs($user)
        ->get(route('results.form', $result))
        ->assertOk()
        ->assertSee($result->referenceNumber())
        ->assertSee('OFFICIAL RESULT FORM');

    expect($result->fresh()->status)->toBe(ResultStatus::Encoded)
        ->and($result->fresh()->form_generated_version)->toBe(1)
        ->and(AuditLog::query()->where('action', 'result_form.generated')->exists())->toBeTrue();
})->with([
    MeetSportAssignmentRole::TournamentManager,
    MeetSportAssignmentRole::AssistantTournamentManager,
    MeetSportAssignmentRole::TournamentSecretary,
    MeetSportAssignmentRole::TournamentICT,
]);

test('sport personnel cannot generate a result form for an unrelated sport', function () {
    $own = resultWithPlacement();
    $other = resultWithPlacement();
    $user = assignedResultUser($own, MeetSportAssignmentRole::TournamentSecretary);

    $this->actingAs($user)->get(route('results.form', $other))->assertForbidden();
});

test('central Event Secretariat can submit an encoded Event Result', function () {
    config()->set('pmms.results.signed_result_form_required', false);
    $result = resultWithPlacement();
    $secretariat = eventSecretariatFor($result);

    $this->actingAs($secretariat)->post("/results/{$result->id}/submit")
        ->assertSessionDoesntHaveErrors();

    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)
        ->and($result->fresh()->submitted_by)->toBe($secretariat->id);
});

test('system administrator can submit an encoded result without a tournament ICT assignment', function () {
    config()->set('pmms.results.signed_result_form_required', false);
    $result = resultWithPlacement();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/results')->assertOk();
    $this->actingAs($admin)->post("/results/{$result->id}/submit")
        ->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)
        ->and($result->fresh()->submitted_by)->toBe($admin->id);
});

test('ICT can submit a completed unscheduled match result with deferred workflow issues', function () {
    config()->set('pmms.results.signed_result_form_required', false);
    $result = resultWithPlacement();
    $result->forceFill(['match_id' => \App\Models\EventMatch::factory()->create([
        'meet_id' => $result->meet_id,
        'event_id' => $result->event_id,
        'event_schedule_id' => null,
        'status' => \App\Enums\MatchStatus::Completed,
    ])->id, 'event_schedule_id' => null, 'tm_confirmed_at' => null])->save();
    $ict = assignedResultUser($result, MeetSportAssignmentRole::TournamentICT);

    $this->actingAs($ict)->post(route('results.submit', $result))
        ->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)
        ->and($result->fresh()->submitted_by)->toBe($ict->id);
});

test('authorized result staff can attach and replace a written result photo', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $result = resultWithPlacement();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('results.photo.store', $result), [
        'photo' => UploadedFile::fake()->image('written-result.jpg'),
    ])->assertRedirect()->assertSessionDoesntHaveErrors();
    $first = $result->attachments()->where('attachment_type', ResultAttachment::RESULT_PHOTO)->sole();

    $this->actingAs($admin)->get(route('results.photo.show', [$result, $first]))->assertOk();
    $this->actingAs($admin)->post(route('results.photo.store', $result), [
        'photo' => UploadedFile::fake()->image('corrected-result.png'),
    ])->assertRedirect()->assertSessionDoesntHaveErrors();

    expect($first->fresh()->is_current)->toBeFalse()
        ->and($result->attachments()->where('attachment_type', ResultAttachment::RESULT_PHOTO)->where('is_current', true)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', 'result_photo.uploaded')->count())->toBe(2);

    $this->actingAs($admin)->get('/results')->assertInertia(fn ($page) => $page
        ->where('results.data.0.result_photo.name', 'corrected-result.png'));
});

test('draft result may exist without attachment but cannot be submitted when signed form is required', function () {
    $result = resultWithPlacement();
    $user = assignedResultUser($result, MeetSportAssignmentRole::TournamentManager);

    expect($result->status)->toBe(ResultStatus::Encoded)
        ->and($result->attachments()->count())->toBe(0);

    $this->actingAs($user)
        ->post(route('results.submit', $result))
        ->assertSessionHasErrors('file');
});

test('authorized personnel upload a versioned signed form and submit it', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $result = resultWithPlacement();
    $user = assignedResultUser($result, MeetSportAssignmentRole::TournamentICT);

    $this->actingAs($user)
        ->post(route('results.attachments.store', $result), [
            'file' => UploadedFile::fake()->image('signed-result.png'),
        ])
        ->assertSessionHasNoErrors();

    $attachment = ResultAttachment::query()->firstOrFail();
    expect($attachment->result_version)->toBe(1)
        ->and($attachment->is_current)->toBeTrue()
        ->and($attachment->checksum_sha256)->toHaveLength(64);

    $this->actingAs($user)->post(route('results.submit', $result))->assertSessionHasNoErrors();
    expect($result->fresh()->status)->toBe(ResultStatus::Submitted)
        ->and(AuditLog::query()->where('action', 'result.submitted')->exists())->toBeTrue();
});

test('unauthorized personnel cannot upload a signed result form', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $result = resultWithPlacement();

    $this->actingAs(User::factory()->create())
        ->post(route('results.attachments.store', $result), [
            'file' => UploadedFile::fake()->create('signed.pdf', 20, 'application/pdf'),
        ])
        ->assertForbidden();
});

test('event secretariat validates and only top management can make a submitted event result official', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $result = resultWithPlacement();
    $sportUser = assignedResultUser($result, MeetSportAssignmentRole::TournamentSecretary);
    $secretariat = eventSecretariatFor($result);
    $topManagement = topManagementFor($result);

    $this->actingAs($sportUser)->post(route('results.attachments.store', $result), [
        'file' => UploadedFile::fake()->create('signed.pdf', 20, 'application/pdf'),
    ]);
    $this->actingAs($sportUser)->post(route('results.submit', $result));
    $attachment = ResultAttachment::query()->firstOrFail();

    $this->actingAs($secretariat)
        ->get(route('results.attachments.download', [$result, $attachment]))
        ->assertOk();
    $this->actingAs($secretariat)
        ->post(route('results.event-secretariat.validate', $result))
        ->assertSessionHasNoErrors();
    $this->actingAs($secretariat)
        ->post(route('results.official', $result))
        ->assertForbidden();

    expect($result->fresh()->status)->toBe(ResultStatus::Validated);

    $this->actingAs($topManagement)
        ->post(route('results.official', $result))
        ->assertSessionHasNoErrors();

    expect($result->fresh()->status)->toBe(ResultStatus::Official)
        ->and($result->fresh()->official_by)->toBe($topManagement->id)
        ->and($result->fresh()->official_at)->not->toBeNull()
        ->and($result->fresh()->currentSignedForm()?->id)->toBe($attachment->id)
        ->and(AuditLog::query()->where('action', 'result.made_official')->exists())->toBeTrue();

    $this->actingAs($sportUser)->put(route('results.update', $result), [])->assertForbidden();
    expect($result->fresh()->status)->toBe(ResultStatus::Official);
});

test('top management cannot officialize an operational match result', function () {
    $result = resultWithPlacement();
    $result->forceFill([
        'result_scope' => 'match',
        'status' => ResultStatus::Validated,
        'validated_at' => now(),
    ])->save();

    $this->actingAs(topManagementFor($result))
        ->post(route('results.official', $result))
        ->assertStatus(422);

    expect($result->fresh()->status)->toBe(ResultStatus::Validated)
        ->and($result->fresh()->medalAwards()->count())->toBe(0);
});

test('returning a result preserves the old attachment and requires a new version attachment', function () {
    Storage::fake('local');
    config()->set('uploads.disk', 'local');
    $result = resultWithPlacement();
    $sportUser = assignedResultUser($result, MeetSportAssignmentRole::TournamentManager);
    $secretariat = eventSecretariatFor($result);

    $this->actingAs($sportUser)->post(route('results.attachments.store', $result), [
        'file' => UploadedFile::fake()->create('version-1.pdf', 20, 'application/pdf'),
    ]);
    $this->actingAs($sportUser)->post(route('results.submit', $result));
    $first = ResultAttachment::query()->firstOrFail();

    $this->actingAs($secretariat)->post(route('results.return', $result), [
        'reason' => 'Signed form does not match the electronic result.',
    ])->assertSessionHasNoErrors();

    expect($result->fresh()->status)->toBe(ResultStatus::Returned)
        ->and($result->fresh()->version)->toBe(2)
        ->and($first->fresh())->not->toBeNull()
        ->and($result->fresh()->currentSignedForm())->toBeNull();

    $this->actingAs($sportUser)->post(route('results.attachments.store', $result), [
        'file' => UploadedFile::fake()->create('version-2.pdf', 20, 'application/pdf'),
    ])->assertSessionHasNoErrors();

    expect(ResultAttachment::query()->count())->toBe(2)
        ->and(ResultAttachment::query()->latest('id')->first()->result_version)->toBe(2);
});
