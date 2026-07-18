<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Models\FileUpload;
use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileUploadController extends Controller
{
    public function __construct(private readonly FileUploadService $uploads) {}

    /**
     * Store an uploaded file.
     */
    public function store(FileUploadRequest $request): RedirectResponse
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');

        /** @var User $user */
        $user = $request->user();

        $this->uploads->store($file, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File uploaded.')]);

        return back();
    }

    /**
     * Download a stored file.
     */
    public function download(FileUpload $upload): StreamedResponse
    {
        Gate::authorize('view', $upload);

        return Storage::disk($upload->disk)->download($upload->path, $upload->original_name);
    }

    /**
     * Delete a stored file.
     */
    public function destroy(FileUpload $upload): RedirectResponse
    {
        Gate::authorize('delete', $upload);

        $this->uploads->delete($upload);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('File deleted.')]);

        return back();
    }
}
