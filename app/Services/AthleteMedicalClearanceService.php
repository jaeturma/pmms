<?php

namespace App\Services;

use App\Enums\EligibilityDocumentType;
use App\Enums\MedicalClearanceStatus;
use App\Models\Athlete;
use App\Models\MedicalClearance;
use App\Models\User;

class AthleteMedicalClearanceService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function clearWhenCertificateAttached(Athlete $athlete, ?User $actor = null): bool
    {
        if (! $athlete->eligibilityDocuments()
            ->where('document_type', EligibilityDocumentType::MedicalCertificate->value)
            ->exists()) {
            return false;
        }

        $athlete->loadMissing('delegation');
        $clearance = MedicalClearance::query()->firstOrNew([
            'athlete_id' => $athlete->id,
        ]);

        if ($clearance->exists && $clearance->status === MedicalClearanceStatus::Cleared) {
            return false;
        }

        $previousStatus = $clearance->exists ? $clearance->status->value : null;
        $clearance->fill([
            'meet_id' => $athlete->delegation->meet_id,
            'personnel_id' => null,
            'status' => MedicalClearanceStatus::Cleared,
        ])->save();

        $this->audit->record('athlete.medical.cleared', $clearance, [
            'athlete_id' => $athlete->id,
            'athlete' => $athlete->fullName(),
            'source' => 'medical_certificate_attached',
            'previous_status' => $previousStatus,
        ], $actor);

        return true;
    }
}
