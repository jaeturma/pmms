<?php

namespace App\Models;

use App\Enums\EligibilityDocumentType;
use App\Enums\RequirementStatus;
use Database\Factories\EligibilityDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $athlete_id
 * @property int $file_upload_id
 * @property EligibilityDocumentType $document_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['athlete_id', 'file_upload_id', 'document_type', 'status', 'school_id', 'school_year', 'examination_date', 'guardian_name', 'guardian_relationship', 'signed_at', 'verified_by', 'verified_at', 'remarks'])]
class EligibilityDocument extends Model
{
    /** @use HasFactory<EligibilityDocumentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'document_type' => EligibilityDocumentType::class,
            'status' => RequirementStatus::class,
            'examination_date' => 'date',
            'signed_at' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Athlete, $this>
     */
    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    /**
     * @return BelongsTo<FileUpload, $this>
     */
    public function fileUpload(): BelongsTo
    {
        return $this->belongsTo(FileUpload::class);
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
}
