<?php

namespace App\Http\Controllers;

use App\Enums\EligibilityDocumentType;
use App\Enums\EligibilityStatus;
use App\Enums\Permission;
use App\Enums\RequirementStatus;
use App\Enums\UserRole;
use App\Models\Athlete;
use App\Models\Meet;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AthleteReadinessController extends Controller
{
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $meet = Meet::query()->find($request->integer('meet_id')) ?? Meet::current();
        abort_unless($user->hasRole(UserRole::Admin, UserRole::Organizer) || $user->hasPermission(Permission::AthleteEligibilityReview, $meet)
            || $user->hasPermission(Permission::DistrictReadinessView, $meet) || $user->hasPermission(Permission::MunicipalityReadinessView, $meet), 403);
        $query = Athlete::query()->with(['school.schoolDistrict', 'eligibilityDocuments', 'eligibilityReview', 'medicalClearance', 'accreditation'])
            ->whereHas('delegation', fn ($scope) => $scope->where('meet_id', $meet->id));
        if (! $user->hasRole(UserRole::Admin, UserRole::Organizer) && ! $user->hasPermission(Permission::AthleteEligibilityReview, $meet)) {
            $assignments = $user->athleteOversightAssignments()->where('active', true)->where('meet_id', $meet->id)->get();
            $query->whereHas('school', fn ($school) => $school->where(function ($scope) use ($assignments): void {
                foreach ($assignments as $assignment) $scope->orWhere(fn ($item) => $assignment->school_district_id !== null ? $item->where('school_district_id', $assignment->school_district_id) : $item->where('district_id', $assignment->district_id));
            }));
        }
        $required = [EligibilityDocumentType::SchoolId, EligibilityDocumentType::BirthCertificate, EligibilityDocumentType::MedicalCertificate, EligibilityDocumentType::ParentalConsent];
        $athletes = $query->get()->map(function (Athlete $athlete) use ($required): array {
            $documentsComplete = collect($required)->every(fn ($type) => $athlete->eligibilityDocuments->contains(fn ($document) => $document->document_type === $type && ($type === EligibilityDocumentType::MedicalCertificate || $document->status === RequirementStatus::Verified)));
            $dsacApproved = $athlete->eligibilityReview?->status === EligibilityStatus::Approved;
            $medicalCleared = $athlete->eligibilityDocuments->contains(fn ($document) => $document->document_type === EligibilityDocumentType::MedicalCertificate);
            return ['id' => $athlete->id, 'name' => $athlete->fullName(), 'school' => $athlete->school?->name ?? 'Not provided', 'school_district' => $athlete->school?->schoolDistrict?->name ?? 'Unassigned School District',
                'documents_complete' => $documentsComplete, 'dsac_approved' => $dsacApproved, 'medical_cleared' => $medicalCleared, 'accredited' => $athlete->accreditation !== null,
                'needs_attention' => ! $documentsComplete || ! $dsacApproved || ! $medicalCleared || $athlete->accreditation === null];
        });
        return Inertia::render('readiness/index', ['meet' => $meet->only(['id', 'name']), 'summary' => ['athletes' => $athletes->count(), 'documents_complete' => $athletes->where('documents_complete', true)->count(),
            'dsac_approved' => $athletes->where('dsac_approved', true)->count(), 'medical_cleared' => $athletes->where('medical_cleared', true)->count(), 'accredited' => $athletes->where('accredited', true)->count(), 'needs_attention' => $athletes->where('needs_attention', true)->count()],
            'districts' => $athletes->groupBy('school_district')->map(fn ($rows, $name) => ['name' => $name, 'athletes' => $rows->count(), 'ready' => $rows->where('needs_attention', false)->count(), 'needs_attention' => $rows->where('needs_attention', true)->count()])->values(),
            'needsAttention' => $athletes->where('needs_attention', true)->values()]);
    }
}
