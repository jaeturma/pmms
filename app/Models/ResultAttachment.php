<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_result_id', 'file_upload_id', 'attachment_type', 'result_version', 'checksum_sha256', 'uploaded_by', 'is_current', 'notes'])]
class ResultAttachment extends Model
{
    public const SIGNED_RESULT_FORM = 'signed_result_form';

    protected function casts(): array
    {
        return ['result_version' => 'integer', 'is_current' => 'boolean'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(EventResult::class, 'event_result_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class, 'file_upload_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
