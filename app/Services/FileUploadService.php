<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class FileUploadService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * How many times to retry the actual disk write before giving up. A
     * freshly-written temp upload file can be transiently locked by
     * antivirus/indexing scans on some hosts — a short retry absorbs that
     * without needing the user to resubmit the whole form.
     */
    private const MAX_ATTEMPTS = 3;

    private const RETRY_DELAY_MS = 150;

    /**
     * Store an uploaded file on the configured disk and record its metadata.
     *
     * `$field` names the form field this came from (e.g. 'logo', 'photo') so
     * a storage failure surfaces as a normal, field-attached validation
     * error instead of a raw 500.
     */
    public function store(UploadedFile $file, User $user, string $field = 'file'): FileUpload
    {
        $disk = $this->disk();
        $destination = trim($this->directory().'/'.$file->hashName(), '/');
        $stored = false;
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $lastException = null;

            try {
                $stored = $this->writeToDisk($file, $disk, $destination);
            } catch (Throwable $e) {
                $lastException = $e;
                $stored = false;
            }

            if ($stored) {
                break;
            }

            if ($attempt < self::MAX_ATTEMPTS) {
                usleep(self::RETRY_DELAY_MS * 1000);
            }
        }

        if (! $stored) {
            // Swallowed into a friendly validation error below, but the
            // real cause (disk misconfiguration, permissions, a full
            // volume) is worth keeping in the log rather than losing
            // entirely — this is exactly what the previous unhandled
            // "Path must not be empty" crash needed to be diagnosable.
            Log::error('File upload storage failed after '.self::MAX_ATTEMPTS.' attempts', [
                'field' => $field,
                'disk' => $disk,
                'original_name' => $file->getClientOriginalName(),
                'exception' => $lastException === null ? null : $lastException::class,
                'message' => $lastException?->getMessage(),
            ]);

            throw ValidationException::withMessages([
                $field => __('The file could not be uploaded. Please try again.'),
            ]);
        }

        $upload = FileUpload::create([
            'uploaded_by' => $user->id,
            'disk' => $disk,
            'path' => $destination,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
        ]);

        $this->auditLogger->record('file.uploaded', $upload, [
            'original_name' => $upload->original_name,
            'size' => $upload->size,
        ], $user);

        return $upload;
    }

    /**
     * Writes the file's raw temp path (`getPathname()`) rather than going
     * through Laravel's own `UploadedFile::store()`, which internally
     * resolves `getRealPath()` — that calls PHP's `realpath()`, which can
     * spuriously return `false` on Windows even though the temp file is
     * perfectly readable at its original (non-canonicalized) path. That's
     * exactly what turned into "Path must not be empty" from `fopen('', 'r')`
     * in production: `getRealPath()` failed, `getPathname()` didn't.
     */
    private function writeToDisk(UploadedFile $file, string $disk, string $destination): bool
    {
        $stream = fopen($file->getPathname(), 'r');

        if ($stream === false) {
            throw new RuntimeException('Unable to open the uploaded file for reading.');
        }

        try {
            return (bool) Storage::disk($disk)->put($destination, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * Delete the stored file and its metadata record.
     */
    public function delete(FileUpload $fileUpload): void
    {
        Storage::disk($fileUpload->disk)->delete($fileUpload->path);

        $original = $fileUpload->original_name;

        $fileUpload->delete();

        $this->auditLogger->record('file.deleted', null, [
            'original_name' => $original,
            'file_upload_id' => $fileUpload->id,
        ]);
    }

    private function disk(): string
    {
        return (string) config('uploads.disk');
    }

    private function directory(): string
    {
        return (string) config('uploads.directory');
    }
}
