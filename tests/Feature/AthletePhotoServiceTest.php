<?php

use App\Models\User;
use App\Services\AthletePhotoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

test('athlete photos are normalized below the size ceiling with derivatives', function (string $type) {
    Storage::fake('local');
    $file = UploadedFile::fake()->image("{$type}.jpg", 2400, 3200);

    $upload = app(AthletePhotoService::class)->store($file, User::factory()->create(), $type);

    Storage::disk('local')->assertExists($upload->path);
    expect(Storage::disk('local')->size($upload->path))->toBeLessThanOrEqual(500 * 1024);
    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($upload->path));
    expect($width)->toBe(800)->and($height)->toBe(1000);

    foreach (['thumb' => [200, 250], 'card' => [480, 600]] as $variant => [$expectedWidth, $expectedHeight]) {
        $path = app(AthletePhotoService::class)->variantPath($upload, $variant);
        Storage::disk('local')->assertExists($path);
        [$variantWidth, $variantHeight] = getimagesizefromstring(Storage::disk('local')->get($path));
        expect($variantWidth)->toBe($expectedWidth)->and($variantHeight)->toBe($expectedHeight);
    }
})->with(['passport', 'sports']);

test('server image decoding rejects a disguised non image', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->createWithContent('fake.jpg', '<script>not an image</script>');

    expect(fn () => app(AthletePhotoService::class)->store($file, User::factory()->create(), 'passport'))
        ->toThrow(ValidationException::class);
});

test('deleting an athlete photo removes the main file and every derivative', function () {
    Storage::fake('local');
    $service = app(AthletePhotoService::class);
    $upload = $service->store(UploadedFile::fake()->image('photo.jpg', 1200, 1600), User::factory()->create(), 'sports');
    $paths = [$upload->path, $service->variantPath($upload, 'thumb'), $service->variantPath($upload, 'card')];

    $service->delete($upload);

    foreach ($paths as $path) {
        Storage::disk('local')->assertMissing($path);
    }
    $this->assertDatabaseMissing('file_uploads', ['id' => $upload->id]);
});
