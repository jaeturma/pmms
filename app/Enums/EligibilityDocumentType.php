<?php

namespace App\Enums;

enum EligibilityDocumentType: string
{
    case AthleteHistory = 'athlete_history';
    case Form10 = 'form_10';
    case SchoolId = 'school_id';
    case BirthCertificate = 'birth_certificate';
    case MedicalCertificate = 'medical_certificate';
    case EnrollmentProof = 'enrollment_proof';
    case ReportCard = 'report_card';
    case ParentalConsent = 'parental_consent';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AthleteHistory => 'Athlete History',
            self::Form10 => 'School Form 10',
            self::SchoolId => 'School ID',
            self::BirthCertificate => 'PSA Birth Certificate',
            self::MedicalCertificate => 'Medical Certificate',
            self::EnrollmentProof => 'Proof of Enrollment',
            self::ReportCard => 'Report Card',
            self::ParentalConsent => 'Parents Consent',
            self::Other => 'Other Document',
        };
    }

    public function verificationPermission(): Permission
    {
        return $this === self::MedicalCertificate
            ? Permission::MedicalClearanceEvaluate
            : Permission::AthleteDocumentsVerify;
    }

    /** @return list<self> */
    public static function qualificationRequirements(): array
    {
        return [
            self::AthleteHistory,
            self::Form10,
            self::BirthCertificate,
            self::ParentalConsent,
            self::MedicalCertificate,
        ];
    }
}
