<?php

namespace App\Models;

use App\Enums\RequirementStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'sport_id', 'file_upload_id', 'accreditation_type', 'certificate_number', 'issuing_organization', 'level', 'issued_at', 'expires_at', 'status', 'verified_by', 'verified_at', 'remarks'])]
class TechnicalOfficialAccreditation extends Model
{
    protected function casts(): array
    {
        return ['status' => RequirementStatus::class, 'issued_at' => 'date', 'expires_at' => 'date', 'verified_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
