<?php

namespace App\Policies;

use App\Models\FileUpload;
use App\Models\User;

class FileUploadPolicy
{
    /**
     * Determine whether the user can download the file.
     */
    public function view(User $user, FileUpload $fileUpload): bool
    {
        return $user->id === $fileUpload->uploaded_by;
    }

    /**
     * Determine whether the user can delete the file.
     */
    public function delete(User $user, FileUpload $fileUpload): bool
    {
        return $user->id === $fileUpload->uploaded_by;
    }
}
