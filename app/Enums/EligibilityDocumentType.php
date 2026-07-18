<?php

namespace App\Enums;

enum EligibilityDocumentType: string
{
    case BirthCertificate = 'birth_certificate';
    case EnrollmentProof = 'enrollment_proof';
    case ReportCard = 'report_card';
    case ParentalConsent = 'parental_consent';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::BirthCertificate => 'Birth Certificate',
            self::EnrollmentProof => 'Proof of Enrollment',
            self::ReportCard => 'Report Card',
            self::ParentalConsent => 'Parental Consent',
            self::Other => 'Other Document',
        };
    }
}
