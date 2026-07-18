<?php

use App\Models\AuditLog;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('logging in records an audit log entry', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $log = AuditLog::query()->where('action', 'auth.login')->sole();

    expect($log->user_id)->toBe($user->id)
        ->and($log->ip_address)->not->toBeNull();
});

test('logging out records an audit log entry', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $log = AuditLog::query()->where('action', 'auth.logout')->sole();

    expect($log->user_id)->toBe($user->id);
});

test('uploading a file records an audit log entry', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ]);

    $upload = FileUpload::query()->sole();
    $log = AuditLog::query()->where('action', 'file.uploaded')->sole();

    expect($log->user_id)->toBe($user->id)
        ->and($log->auditable_type)->toBe($upload->getMorphClass())
        ->and($log->auditable_id)->toBe($upload->id)
        ->and($log->context)->toMatchArray(['original_name' => 'report.pdf']);
});

test('deleting a file records an audit log entry', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ]);

    $upload = FileUpload::query()->sole();

    $this->actingAs($user)->delete(route('uploads.destroy', $upload));

    $log = AuditLog::query()->where('action', 'file.deleted')->sole();

    expect($log->user_id)->toBe($user->id)
        ->and($log->context)->toMatchArray(['original_name' => 'report.pdf']);
});

test('audit entries can be recorded without an authenticated user', function () {
    $log = app(AuditLogger::class)->record('system.test', null, ['note' => 'scheduled task']);

    expect($log->user_id)->toBeNull()
        ->and($log->action)->toBe('system.test')
        ->and($log->context)->toMatchArray(['note' => 'scheduled task']);
});

test('audit log user survives user deletion as null', function () {
    $user = User::factory()->create();
    $log = AuditLog::factory()->for($user)->create();

    $user->delete();

    expect($log->fresh()?->user_id)->toBeNull();
});
