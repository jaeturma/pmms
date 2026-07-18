<?php

use App\Models\AuditLog;
use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('guests cannot upload files', function () {
    $response = $this->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('login'));
});

test('authenticated users can upload an allowed file', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $upload = FileUpload::query()->sole();

    expect($upload->uploaded_by)->toBe($user->id)
        ->and($upload->original_name)->toBe('report.pdf')
        ->and($upload->disk)->toBe('local');

    Storage::disk('local')->assertExists($upload->path);
});

test('uploads with a disallowed file type are rejected', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
    ]);

    $response->assertSessionHasErrors('file');

    expect(FileUpload::query()->count())->toBe(0);
});

test('uploads over the size limit are rejected', function () {
    $user = User::factory()->create();

    $tooLargeKb = (int) config('uploads.max_kb') + 1;

    $response = $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('big.pdf', $tooLargeKb, 'application/pdf'),
    ]);

    $response->assertSessionHasErrors('file');

    expect(FileUpload::query()->count())->toBe(0);
});

test('owners can download their file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ]);

    $upload = FileUpload::query()->sole();

    $response = $this->actingAs($user)->get(route('uploads.download', $upload));

    $response->assertOk();
    $response->assertDownload('report.pdf');

    expect(AuditLog::query()->where('action', 'file.downloaded')->count())->toBe(1);
});

test('users cannot download files they do not own', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $upload = FileUpload::factory()->for($owner, 'uploader')->create();

    $response = $this->actingAs($other)->get(route('uploads.download', $upload));

    $response->assertForbidden();
});

test('owners can delete their file', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('uploads.store'), [
        'file' => UploadedFile::fake()->create('report.pdf', 100, 'application/pdf'),
    ]);

    $upload = FileUpload::query()->sole();
    $path = $upload->path;

    $response = $this->actingAs($user)->delete(route('uploads.destroy', $upload));

    $response->assertRedirect();

    expect(FileUpload::query()->count())->toBe(0);

    Storage::disk('local')->assertMissing($path);
});

test('users cannot delete files they do not own', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $upload = FileUpload::factory()->for($owner, 'uploader')->create();

    $response = $this->actingAs($other)->delete(route('uploads.destroy', $upload));

    $response->assertForbidden();

    expect(FileUpload::query()->count())->toBe(1);
});
