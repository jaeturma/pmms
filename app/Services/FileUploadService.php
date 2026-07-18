<?php

namespace App\Services;

use App\Models\FileUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class FileUploadService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Store an uploaded file on the configured disk and record its metadata.
     */
    public function store(UploadedFile $file, User $user): FileUpload
    {
        $disk = $this->disk();

        $path = $file->store($this->directory(), $disk);

        if ($path === false) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        $upload = FileUpload::create([
            'uploaded_by' => $user->id,
            'disk' => $disk,
            'path' => $path,
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
