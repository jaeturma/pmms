<?php

namespace App\Enums;

enum Permission: string
{
    case ContentView = 'content.view';
    case NewsManage = 'news.manage';
    case AnnouncementsManage = 'announcements.manage';
    case FaqManage = 'faq.manage';
    case GalleryUploadCandidate = 'gallery.upload_candidate';
    case GalleryReview = 'gallery.review';
    case GalleryPublish = 'gallery.publish';
    case AthleteRegistrationCreate = 'athlete.registration.create';
    case AthleteRegistrationView = 'athlete.registration.view';
    case AthleteProfileValidate = 'athlete.profile.validate';
    case AthleteDocumentsVerify = 'athlete.documents.verify';
    case AthleteEligibilityReview = 'athlete.eligibility.review';
    case AthleteEligibilityApprove = 'athlete.eligibility.approve';
    case MedicalClearanceEvaluate = 'medical.clearance.evaluate';
    case MedicalClearanceApprove = 'medical.clearance.approve';
    case DistrictReadinessView = 'district.readiness.view';
    case DistrictAthletesView = 'district.athletes.view';
    case MunicipalityReadinessView = 'municipality.readiness.view';
    case MunicipalityAthletesView = 'municipality.athletes.view';
}
