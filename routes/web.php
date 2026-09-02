<?php

use App\Enums\SportPortalSlug;
use App\Http\Controllers\AccountActivationController;
use App\Http\Controllers\AccountProvisionController;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\AthleteReadinessController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BilletingAssignmentController;
use App\Http\Controllers\BilletingVenueController;
use App\Http\Controllers\CoachAssignmentRequestController;
use App\Http\Controllers\ContentManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\DemoDataController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DrrmEquipmentController;
use App\Http\Controllers\DrrmPlanController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\EmergencyCommunicationLogController;
use App\Http\Controllers\EmergencyContactController;
use App\Http\Controllers\EmergencyIncidentController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EquipmentCategoryController;
use App\Http\Controllers\EquipmentIssueController;
use App\Http\Controllers\EquipmentItemController;
use App\Http\Controllers\EquipmentReturnController;
use App\Http\Controllers\EquipmentTransferController;
use App\Http\Controllers\EvacuationRouteController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\InventoryAdjustmentController;
use App\Http\Controllers\ManagementDashboardController;
use App\Http\Controllers\ManagementTeamController;
use App\Http\Controllers\ManagementTeamMemberController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MatchRosterController;
use App\Http\Controllers\MealAnnouncementController;
use App\Http\Controllers\MealScheduleController;
use App\Http\Controllers\MealStubController;
use App\Http\Controllers\MedicalAccessController;
use App\Http\Controllers\MedicalClearanceController;
use App\Http\Controllers\MeetController;
use App\Http\Controllers\MeetSportAssignmentController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalSportsController;
use App\Http\Controllers\PortalTeamsController;
use App\Http\Controllers\ProtestController;
use App\Http\Controllers\ReadinessChecklistController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequiredPasswordChangeController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ResultWorkflowController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolDistrictController;
use App\Http\Controllers\ScoringSessionController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SportRosterController;
use App\Http\Controllers\SwimmingRosterController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\TallyController;
use App\Http\Controllers\TeamEntryController;
use App\Http\Controllers\TechnicalOfficialAccreditationController;
use App\Http\Controllers\TransportRequestController;
use App\Http\Controllers\TransportTripController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\VenueEmergencyPlanController;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class)
    ->middleware('throttle:12,1')
    ->name('health');

Route::middleware('throttle:10,1')->group(function () {
    Route::get('account-activation/{token}', [AccountActivationController::class, 'show'])->name('account-activation.show');
    Route::post('account-activation/{token}', [AccountActivationController::class, 'activate'])->name('account-activation.activate');
});

Route::middleware('auth')->group(function () {
    Route::get('system/demo-data', [DemoDataController::class, 'index'])->name('demo-data.index');
    Route::post('system/demo-data', [DemoDataController::class, 'store'])->middleware('throttle:10,1')->name('demo-data.store');
    Route::get('system/demo-data/{demoScenario}', [DemoDataController::class, 'show'])->name('demo-data.show');
    Route::delete('system/demo-data/{demoScenario}', [DemoDataController::class, 'destroy'])->middleware('throttle:10,1')->name('demo-data.destroy');
    Route::inertia('support', 'support')->name('support');
    Route::get('change-password', [RequiredPasswordChangeController::class, 'edit'])->name('password-change.edit');
    Route::put('change-password', [RequiredPasswordChangeController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password-change.update');
});

// Public portal — guest routes, throttled, published data only.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('about', [PortalController::class, 'currentAbout'])->name('about');
    Route::get('news', [PortalController::class, 'currentNews'])->name('news');
    Route::get('news/{slug}', [PortalController::class, 'newsShow'])->name('news.show');
    Route::get('news-images/{newsItem}', [NewsController::class, 'publicImage'])->name('news.public-image');
    Route::get('gallery', [PortalController::class, 'currentGallery'])->name('gallery');
    Route::get('gallery-images/{galleryItem}', [GalleryController::class, 'publicImage'])->name('gallery.public-image');
    Route::get('faq', [PortalController::class, 'currentFaqs'])->name('faq');
    Route::get('districts/{district}/logo', [DistrictController::class, 'logo'])->name('districts.logo');
    Route::get('districts/{district}/team-logo', [DistrictController::class, 'teamLogo'])->name('districts.team-logo');
    Route::get('sports/{sport}/photo', [SportController::class, 'photo'])->name('sports.photo');
    Route::get('division/logo', [DivisionController::class, 'logo'])->name('division.logo');
    Route::get('division/hero-icon', [DivisionController::class, 'heroIcon'])->name('division.hero-icon');
    Route::get('branding/logo', [SystemSettingsController::class, 'logo'])->name('branding.logo');
    Route::get('branding/favicon', [SystemSettingsController::class, 'favicon'])->name('branding.favicon');
    Route::get('branding/login-background', [SystemSettingsController::class, 'loginBackground'])->name('branding.login-background');
    Route::get('meets/{meet}', [PortalController::class, 'meet'])
        ->whereNumber('meet')
        ->name('public.meet');
    Route::get('meets/{meet}/results', [PortalController::class, 'results'])
        ->whereNumber('meet')
        ->name('public.results');
    Route::get('meets/{meet}/tally', [PortalController::class, 'tally'])
        ->whereNumber('meet')
        ->name('public.tally');
    Route::get('meets/{meet}/rankings', [PortalController::class, 'rankings'])
        ->whereNumber('meet')
        ->name('public.rankings');
    Route::get('meets/{meet}/athletics', [PortalController::class, 'athletics'])
        ->whereNumber('meet')
        ->name('public.athletics');
    Route::get('meets/{meet}/sports', [PortalController::class, 'sports'])
        ->whereNumber('meet')
        ->name('public.sports');
    Route::get('meets/{meet}/gallery', [PortalController::class, 'gallery'])
        ->whereNumber('meet')
        ->name('public.gallery');
    Route::get('meets/{meet}/news', [PortalController::class, 'news'])
        ->whereNumber('meet')
        ->name('public.news');
    Route::get('meets/{meet}/contact', [PortalController::class, 'contact'])
        ->whereNumber('meet')
        ->name('public.contact');
    Route::get('meets/{meet}/about', [PortalController::class, 'about'])
        ->whereNumber('meet')
        ->name('public.about');
    Route::get('meets/{meet}/faqs', [PortalController::class, 'faqs'])
        ->whereNumber('meet')
        ->name('public.faqs');
    Route::get('meets/{meet}/search', [PortalController::class, 'search'])
        ->whereNumber('meet')
        ->name('public.search');
    Route::get('meets/{meet}/matches/{match}/scoreboard', [PortalController::class, 'scoreboard'])
        ->middleware('auth')
        ->whereNumber(['meet', 'match'])
        ->name('public.scoreboard');
    Route::get('meets/{meet}/matches/{match}/scoreboard/poll', [PortalController::class, 'scoreboardPoll'])
        ->middleware('auth')
        ->withoutMiddleware('throttle:60,1')
        ->middleware('throttle:90,1')
        ->whereNumber(['meet', 'match'])
        ->name('public.scoreboard.poll');

    // Public municipal teams/delegations directory — permanent,
    // meet-agnostic routes (same "resolve to whichever meet is currently
    // active" pattern as the Phase 12 sport-portal routes below), not
    // nested under /meets/{meet} since a municipality's identity persists
    // across meets even though the data shown is always the active one's.
    Route::get('teams', [PortalTeamsController::class, 'index'])->name('public.teams');
    Route::get('teams/{municipality}', [PortalTeamsController::class, 'show'])->name('public.teams.show');
    Route::get('teams/{municipality}/players-coaches', [PortalTeamsController::class, 'playersCoaches'])->name('public.teams.players-coaches');

    // Public sports directory — the full 28-sport catalog, browsable by
    // Regular Sports/Paragames, each card linking to its own permanent
    // mini portal below. Deliberately NOT `/sports` — that URI is already
    // claimed by the authenticated admin catalog route (`sports.index`,
    // registered later in this file with an identical bare "sports" URI).
    // Laravel's route collection silently overwrites on identical
    // method+URI, so registering both at `/sports` made guests hit the
    // admin route's `auth` middleware and bounce to `/login` — confirmed
    // with a real request before choosing this path instead of touching
    // the existing admin route. Registered ahead of the `{sportSlug}`
    // catch-all for readability; not actually order-dependent since
    // "sports-directory" isn't one of `SportPortalSlug::values()`.
    Route::get('sports-directory', [PortalSportsController::class, 'index'])->name('public.sports-directory');

    // Dedicated live-scoreboard page (`/live/basketball`, etc.) — the
    // sport portal's own "Live now" section links out here instead of
    // embedding the full scoreboard inline. Reuses the sport portal's
    // own poll endpoint below (`public.sport-portal.poll`) since the
    // payload shape (`liveNow`/`otherLiveCount`) is identical.
    Route::get('live/{sportSlug}', [PortalController::class, 'liveSportPortal'])
        ->middleware('auth')
        ->whereIn('sportSlug', SportPortalSlug::values())
        ->name('public.live-sport-portal');

    // Phase 12: permanent, meet-agnostic sport-portal routes
    // (`/basketball`, etc.) — constrained to the full 28-sport catalog
    // (`SportPortalSlug::values()`) so this can never intercept any other
    // top-level route.
    Route::get('{sportSlug}/poll', [PortalController::class, 'sportPortalPoll'])
        ->middleware('auth')
        ->whereIn('sportSlug', SportPortalSlug::values())
        ->name('public.sport-portal.poll');
    Route::get('{sportSlug}', [PortalController::class, 'sportPortal'])
        ->whereIn('sportSlug', SportPortalSlug::values())
        ->name('public.sport-portal');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('coach/assignment-requests', [CoachAssignmentRequestController::class, 'store'])->name('coach.assignment-requests.store');
    Route::get('coach/assignment-requests', [CoachAssignmentRequestController::class, 'index'])->name('coach.assignment-requests.index');
    Route::patch('coach/assignment-requests/{coachAssignmentRequest}', [CoachAssignmentRequestController::class, 'review'])->name('coach.assignment-requests.review');
    Route::post('coach/assignment-requests/{coachAssignmentRequest}/reset-password', [CoachAssignmentRequestController::class, 'resetPassword'])
        ->middleware('throttle:6,1')->name('coach.assignment-requests.reset-password');
    Route::patch('coach/onboarding-requests/{coachOnboardingRequest}', [CoachAssignmentRequestController::class, 'reviewOnboarding'])->name('coach.onboarding-requests.review');
    Route::get('coach/onboarding-requests/{coachOnboardingRequest}/assignments', [CoachAssignmentRequestController::class, 'assignments'])->name('coach.onboarding-assignments.edit');
    Route::put('coach/onboarding-requests/{coachOnboardingRequest}/assignments', [CoachAssignmentRequestController::class, 'syncAssignments'])->name('coach.onboarding-assignments.update');
    Route::post('coach/onboarding-requests/{coachOnboardingRequest}/accredit', [CoachAssignmentRequestController::class, 'accredit'])->name('coach.onboarding-accredit');
    Route::get('coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}', [CoachAssignmentRequestController::class, 'document'])->name('coach.onboarding-documents.show');
    Route::post('coach/onboarding-requests/{coachOnboardingRequest}/documents/{type}', [CoachAssignmentRequestController::class, 'uploadDocument'])->name('coach.onboarding-documents.store');
    Route::post('coach/onboarding-requests/{coachOnboardingRequest}/reset-password', [CoachAssignmentRequestController::class, 'resetOnboardingPassword'])
        ->middleware('throttle:6,1')->name('coach.onboarding-requests.reset-password');

    Route::get('system/users', [UserManagementController::class, 'index'])->name('system.users.index');
    Route::post('system/users', [UserManagementController::class, 'store'])->name('system.users.store');
    Route::put('system/users/{user}', [UserManagementController::class, 'update'])->name('system.users.update');
    Route::delete('system/users/{user}', [UserManagementController::class, 'destroy'])->name('system.users.destroy');
    Route::post('system/users/{user}/approve', [UserManagementController::class, 'approve'])->name('system.users.approve');
    Route::post('system/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
        ->middleware('throttle:6,1')->name('system.users.reset-password');
    Route::post('accreditations', [AccreditationController::class, 'store'])->name('accreditation.store');

    Route::post('uploads', [FileUploadController::class, 'store'])->name('uploads.store');
    Route::get('uploads/{upload}', [FileUploadController::class, 'download'])->name('uploads.download');
    Route::delete('uploads/{upload}', [FileUploadController::class, 'destroy'])->name('uploads.destroy');

    Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::get('school-districts', [SchoolDistrictController::class, 'index'])->name('school-districts.index');
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::put('schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
    Route::patch('schools/{school}/archive', [SchoolController::class, 'archive'])->name('schools.archive');
    Route::patch('schools/{school}/restore', [SchoolController::class, 'restore'])->name('schools.restore');
    Route::delete('schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');
    Route::get('sports', [SportController::class, 'index'])->name('sports.index');
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('meets', [MeetController::class, 'index'])->name('meets.index');
    Route::get('venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('meet-sport-assignments', [MeetSportAssignmentController::class, 'index'])->name('meet-sport-assignments.index');
    Route::post('meet-sport-assignments', [MeetSportAssignmentController::class, 'store'])->name('meet-sport-assignments.store');
    Route::patch('meet-sport-assignments/{meetSportAssignment}/status', [MeetSportAssignmentController::class, 'updateStatus'])->name('meet-sport-assignments.status');
    Route::delete('meet-sport-assignments/{meetSportAssignment}', [MeetSportAssignmentController::class, 'destroy'])->name('meet-sport-assignments.destroy');
    Route::get('management-teams', [ManagementTeamController::class, 'index'])->name('management-teams.index');
    Route::post('management-team-members', [ManagementTeamMemberController::class, 'store'])->name('management-team-members.store');
    Route::patch('management-team-members/{managementTeamMember}/status', [ManagementTeamMemberController::class, 'updateStatus'])->name('management-team-members.status');
    Route::delete('management-team-members/{managementTeamMember}', [ManagementTeamMemberController::class, 'destroy'])->name('management-team-members.destroy');
    Route::get('equipment', [EquipmentCategoryController::class, 'index'])->name('equipment.index');
    Route::get('food', [MealScheduleController::class, 'index'])->name('food.index');
    Route::get('meal-stub', [MealStubController::class, 'show'])->name('meal-stub.show');
    Route::post('meal-stub/{mealEntitlement}/consume', [MealStubController::class, 'consumeOwn'])->name('meal-stub.consume');
    Route::get('food/distribution', [MealStubController::class, 'distribution'])->name('food.distribution');
    Route::get('food/meal-stubs/print', [MealStubController::class, 'batchPrint'])->name('food.meal-stubs.print');
    Route::get('food/meal-stubs/template', [MealStubController::class, 'templatePrint'])->name('food.meal-stubs.template');
    Route::post('food/distribution/{mealEntitlement}/consume', [MealStubController::class, 'consumeStaff'])->name('food.distribution.consume');
    Route::get('billeting', [BilletingVenueController::class, 'index'])->name('billeting.index');
    Route::get('transport', [VehicleController::class, 'index'])->name('transport.index');
    Route::get('medical', [MedicalClearanceController::class, 'index'])->name('medical.index');
    Route::get('drrm/plans', [DrrmPlanController::class, 'index'])->name('drrm-plans.index');
    Route::get('drrm/incidents', [EmergencyIncidentController::class, 'index'])->name('emergency-incidents.index');

    Route::get('delegations', [DelegationController::class, 'index'])->name('delegations.index');
    Route::put('delegations/{delegation}', [DelegationController::class, 'update'])->name('delegations.update');
    Route::patch('delegations/{delegation}/submit', [DelegationController::class, 'submit'])->name('delegations.submit');
    Route::patch('delegations/{delegation}/approve', [DelegationController::class, 'approve'])->name('delegations.approve');
    Route::patch('delegations/{delegation}/return', [DelegationController::class, 'returnToDraft'])->name('delegations.return');
    Route::put('delegations/{delegation}/officers', [DelegationController::class, 'syncOfficers'])->name('delegations.officers');

    Route::get('delegations/{delegation}/accreditation', [AccreditationController::class, 'index'])->name('accreditation.index');
    Route::get('delegations/{delegation}/accreditation/cards', [AccreditationController::class, 'cards'])->name('accreditation.cards');
    Route::get('accreditations/{accreditation}/card', [AccreditationController::class, 'card'])->name('accreditation.card');

    Route::get('athletes', [AthleteController::class, 'index'])->name('athletes.index');
    Route::get('athletes/eligibility', [EligibilityController::class, 'athleteChecker'])->name('athletes.eligibility');
    Route::get('readiness', AthleteReadinessController::class)->name('readiness.index');
    Route::get('monitoring/readiness', \App\Http\Controllers\MeetReadinessController::class)->name('monitoring.readiness.index');
    Route::get('athletes/{athlete}/edit', [AthleteController::class, 'edit'])->name('athletes.edit');
    Route::get('athletes/{athlete}', [AthleteController::class, 'show'])->name('athletes.show');
    Route::get('athletes/{athlete}/photo', [AthleteController::class, 'photo'])->name('athletes.photo');
    Route::get('athletes/{athlete}/sports-photo', [AthleteController::class, 'sportsPhoto'])->name('athletes.sports-photo');
    Route::post('athletes', [AthleteController::class, 'store'])->name('athletes.store');
    Route::put('athletes/{athlete}', [AthleteController::class, 'update'])->name('athletes.update');
    Route::delete('athletes/{athlete}', [AthleteController::class, 'destroy'])->name('athletes.destroy');
    Route::delete('athletes/{athlete}/permanent', [AthleteController::class, 'forceDestroy'])->name('athletes.force-destroy');

    Route::get('personnel', [PersonnelController::class, 'index'])->name('personnel.index');
    Route::get('personnel/{personnel}/photo', [PersonnelController::class, 'photo'])->name('personnel.photo');
    Route::post('personnel', [PersonnelController::class, 'store'])->name('personnel.store');
    Route::put('personnel/{personnel}', [PersonnelController::class, 'update'])->name('personnel.update');
    Route::put('personnel/{personnel}/sports', [PersonnelController::class, 'syncSports'])->name('personnel.sports');
    Route::delete('personnel/{personnel}', [PersonnelController::class, 'destroy'])->name('personnel.destroy');

    Route::get('entries', [EntryController::class, 'index'])->name('entries.index');
    Route::post('entries', [EntryController::class, 'store'])->name('entries.store');
    Route::patch('entries/{entry}/confirm', [EntryController::class, 'confirm'])->name('entries.confirm');
    Route::patch('entries/{entry}/withdraw', [EntryController::class, 'withdraw'])->name('entries.withdraw');
    Route::delete('entries/{entry}', [EntryController::class, 'destroy'])->name('entries.destroy');
    Route::post('team-entries', [TeamEntryController::class, 'store'])->name('team-entries.store');
    Route::patch('team-entries/{teamEntry}/confirm', [TeamEntryController::class, 'confirm'])->name('team-entries.confirm');
    Route::post('sport-rosters/members', [SportRosterController::class, 'store'])->name('sport-rosters.members.store');
    Route::delete('sport-rosters/members/{sportRosterMember}', [SportRosterController::class, 'destroy'])->name('sport-rosters.members.destroy');
    Route::get('swimming/rosters', [SwimmingRosterController::class, 'index'])->name('swimming.rosters.index');

    Route::get('matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('matches/{match}/scoring-session', [ScoringSessionController::class, 'show'])->name('scoring.show');
    Route::get('matches/{match}/scoreboard', [ScoringSessionController::class, 'board'])->name('scoring.board');
    Route::get('results', [ResultController::class, 'index'])->name('results.index');
    Route::get('results/{result}/form', [ResultWorkflowController::class, 'form'])->name('results.form');
    Route::post('results/{result}/attachments', [ResultWorkflowController::class, 'upload'])->name('results.attachments.store');
    Route::get('results/{result}/attachments/{attachment}', [ResultWorkflowController::class, 'download'])->name('results.attachments.download');
    Route::post('results/{result}/submit', [ResultWorkflowController::class, 'submit'])->name('results.submit');
    Route::post('results/{result}/tm-confirmation', [ResultWorkflowController::class, 'confirmByTournamentManager'])->name('results.tm-confirm');
    Route::post('results/{result}/return', [ResultWorkflowController::class, 'returnResult'])->name('results.return');
    Route::post('results/{result}/event-secretariat-validation', [ResultWorkflowController::class, 'validateResult'])->name('results.event-secretariat.validate');
    Route::post('results/{result}/official', [ResultWorkflowController::class, 'makeOfficial'])->name('results.official');
    Route::post('results/{result}/medal-awards/recalculate', [ResultWorkflowController::class, 'recalculateMedalAwards'])->name('results.medal-awards.recalculate');
    Route::post('results/{result}/reopen', [ResultWorkflowController::class, 'reopen'])->name('results.reopen');
    Route::get('tally', [TallyController::class, 'index'])->name('tally.index');

    Route::get('protests', [ProtestController::class, 'index'])->name('protests.index');
    Route::post('protests', [ProtestController::class, 'store'])->name('protests.store');

    // Supply/Equipment (WP-REALIGN-10) — access is Admin/Organizer or a
    // meet's Supply Team, not the flat role:admin,organizer group below,
    // so these stay outside it and authorize per-meet via SupplyPolicy
    // inside each controller, same shape as protests/eligibility above.
    Route::post('equipment-categories', [EquipmentCategoryController::class, 'store'])->name('equipment-categories.store');
    Route::put('equipment-categories/{equipmentCategory}', [EquipmentCategoryController::class, 'update'])->name('equipment-categories.update');
    Route::delete('equipment-categories/{equipmentCategory}', [EquipmentCategoryController::class, 'destroy'])->name('equipment-categories.destroy');

    Route::post('equipment-items', [EquipmentItemController::class, 'store'])->name('equipment-items.store');
    Route::put('equipment-items/{equipmentItem}', [EquipmentItemController::class, 'update'])->name('equipment-items.update');
    Route::delete('equipment-items/{equipmentItem}', [EquipmentItemController::class, 'destroy'])->name('equipment-items.destroy');

    Route::post('equipment-issues', [EquipmentIssueController::class, 'store'])->name('equipment-issues.store');
    Route::post('equipment-returns', [EquipmentReturnController::class, 'store'])->name('equipment-returns.store');
    Route::post('equipment-transfers', [EquipmentTransferController::class, 'store'])->name('equipment-transfers.store');
    Route::post('inventory-adjustments', [InventoryAdjustmentController::class, 'store'])->name('inventory-adjustments.store');

    // Food/Billeting/Transport (WP-REALIGN-11) — same shape as Supply
    // above: access varies by domain (Food is Admin/Organizer/Food Team;
    // Billeting/Transport also allow a DelegationOfficer read-only access
    // to their own delegation's records, plus filing their own transport
    // request), so these stay outside the flat role:admin,organizer group
    // and authorize inside each controller via FoodPolicy/
    // BilletingPolicy/TransportPolicy.
    Route::post('meal-schedules', [MealScheduleController::class, 'store'])->name('meal-schedules.store');
    Route::put('meal-schedules/{mealSchedule}', [MealScheduleController::class, 'update'])->name('meal-schedules.update');
    Route::delete('meal-schedules/{mealSchedule}', [MealScheduleController::class, 'destroy'])->name('meal-schedules.destroy');

    Route::post('meal-announcements', [MealAnnouncementController::class, 'store'])->name('meal-announcements.store');
    Route::put('meal-announcements/{mealAnnouncement}', [MealAnnouncementController::class, 'update'])->name('meal-announcements.update');
    Route::delete('meal-announcements/{mealAnnouncement}', [MealAnnouncementController::class, 'destroy'])->name('meal-announcements.destroy');

    Route::post('billeting-venues', [BilletingVenueController::class, 'store'])->name('billeting-venues.store');
    Route::put('billeting-venues/{billetingVenue}', [BilletingVenueController::class, 'update'])->name('billeting-venues.update');
    Route::delete('billeting-venues/{billetingVenue}', [BilletingVenueController::class, 'destroy'])->name('billeting-venues.destroy');

    Route::post('billeting-assignments', [BilletingAssignmentController::class, 'store'])->name('billeting-assignments.store');
    Route::patch('billeting-assignments/{billetingAssignment}/status', [BilletingAssignmentController::class, 'updateStatus'])->name('billeting-assignments.status');
    Route::delete('billeting-assignments/{billetingAssignment}', [BilletingAssignmentController::class, 'destroy'])->name('billeting-assignments.destroy');

    Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

    Route::post('transport-requests', [TransportRequestController::class, 'store'])->name('transport-requests.store');
    Route::patch('transport-requests/{transportRequest}/status', [TransportRequestController::class, 'updateStatus'])->name('transport-requests.status');
    Route::delete('transport-requests/{transportRequest}', [TransportRequestController::class, 'destroy'])->name('transport-requests.destroy');

    Route::post('transport-trips', [TransportTripController::class, 'store'])->name('transport-trips.store');
    Route::patch('transport-trips/{transportTrip}/status', [TransportTripController::class, 'updateStatus'])->name('transport-trips.status');
    Route::delete('transport-trips/{transportTrip}', [TransportTripController::class, 'destroy'])->name('transport-trips.destroy');

    // Medical/DRRM (WP-REALIGN-12) — same shape as Supply/Food/Billeting/
    // Transport above: access varies by domain (Medical is the one
    // three-tier exception in this whole series — see MedicalPolicy's
    // own docblock), so these stay outside the flat role:admin,organizer
    // group and authorize inside each controller via MedicalPolicy/
    // DrrmPolicy.
    Route::post('medical-clearances', [MedicalClearanceController::class, 'store'])->name('medical-clearances.store');
    Route::put('medical-clearances/{medicalClearance}', [MedicalClearanceController::class, 'update'])->name('medical-clearances.update');

    Route::post('medical-access', [MedicalAccessController::class, 'store'])->name('medical-access.store');
    Route::patch('medical-access/{medicalAccessLog}/review', [MedicalAccessController::class, 'review'])->name('medical-access.review');

    Route::post('drrm-plans', [DrrmPlanController::class, 'store'])->name('drrm-plans.store');
    Route::put('drrm-plans/{drrmPlan}', [DrrmPlanController::class, 'update'])->name('drrm-plans.update');
    Route::delete('drrm-plans/{drrmPlan}', [DrrmPlanController::class, 'destroy'])->name('drrm-plans.destroy');

    Route::post('venue-emergency-plans', [VenueEmergencyPlanController::class, 'store'])->name('venue-emergency-plans.store');
    Route::put('venue-emergency-plans/{venueEmergencyPlan}', [VenueEmergencyPlanController::class, 'update'])->name('venue-emergency-plans.update');
    Route::delete('venue-emergency-plans/{venueEmergencyPlan}', [VenueEmergencyPlanController::class, 'destroy'])->name('venue-emergency-plans.destroy');

    Route::post('evacuation-routes', [EvacuationRouteController::class, 'store'])->name('evacuation-routes.store');
    Route::put('evacuation-routes/{evacuationRoute}', [EvacuationRouteController::class, 'update'])->name('evacuation-routes.update');
    Route::delete('evacuation-routes/{evacuationRoute}', [EvacuationRouteController::class, 'destroy'])->name('evacuation-routes.destroy');

    Route::post('emergency-contacts', [EmergencyContactController::class, 'store'])->name('emergency-contacts.store');
    Route::put('emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'update'])->name('emergency-contacts.update');
    Route::delete('emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'destroy'])->name('emergency-contacts.destroy');

    Route::post('drrm-equipment', [DrrmEquipmentController::class, 'store'])->name('drrm-equipment.store');
    Route::put('drrm-equipment/{drrmEquipment}', [DrrmEquipmentController::class, 'update'])->name('drrm-equipment.update');
    Route::delete('drrm-equipment/{drrmEquipment}', [DrrmEquipmentController::class, 'destroy'])->name('drrm-equipment.destroy');

    Route::post('readiness-checklists', [ReadinessChecklistController::class, 'store'])->name('readiness-checklists.store');
    Route::patch('readiness-checklists/{readinessChecklist}/status', [ReadinessChecklistController::class, 'updateStatus'])->name('readiness-checklists.status');
    Route::delete('readiness-checklists/{readinessChecklist}', [ReadinessChecklistController::class, 'destroy'])->name('readiness-checklists.destroy');

    Route::post('emergency-incidents', [EmergencyIncidentController::class, 'store'])->name('emergency-incidents.store');
    Route::patch('emergency-incidents/{emergencyIncident}/status', [EmergencyIncidentController::class, 'updateStatus'])->name('emergency-incidents.status');

    Route::post('emergency-communication-logs', [EmergencyCommunicationLogController::class, 'store'])->name('emergency-communication-logs.store');

    Route::get('eligibility', [EligibilityController::class, 'index'])->name('eligibility.index');
    Route::get('eligibility/reviews/{review}', [EligibilityController::class, 'show'])->name('eligibility.reviews.show');
    Route::post('eligibility/reviews/{review}/notify-coach', [EligibilityController::class, 'notifyCoach'])->name('eligibility.reviews.notify-coach');
    Route::get('technical-officials/{official}/eligibility', [EligibilityController::class, 'officialChecker'])->name('technical-officials.eligibility');
    Route::post('technical-official-accreditations', [TechnicalOfficialAccreditationController::class, 'store'])->name('technical-official-accreditations.store');
    Route::get('technical-official-accreditations/{accreditation}', [TechnicalOfficialAccreditationController::class, 'download'])->name('technical-official-accreditations.download');
    Route::patch('technical-official-accreditations/{accreditation}/status', [TechnicalOfficialAccreditationController::class, 'updateStatus'])->name('technical-official-accreditations.status');
    Route::post('eligibility/documents', [EligibilityController::class, 'storeDocument'])->name('eligibility.documents.store');
    Route::get('eligibility/documents/{document}', [EligibilityController::class, 'downloadDocument'])->name('eligibility.documents.download');
    Route::delete('eligibility/documents/{document}', [EligibilityController::class, 'destroyDocument'])->name('eligibility.documents.destroy');
    Route::patch('eligibility/documents/{document}/status', [EligibilityController::class, 'verifyDocument'])->name('eligibility.documents.status');
    Route::patch('eligibility/reviews/{review}/approve', [EligibilityController::class, 'approve'])->name('eligibility.approve');
    Route::patch('eligibility/reviews/{review}/return', [EligibilityController::class, 'returnReview'])->name('eligibility.return');
    Route::patch('eligibility/reviews/{review}/reject', [EligibilityController::class, 'reject'])->name('eligibility.reject');

    Route::get('reports/participation', [ReportController::class, 'participation'])->name('reports.participation');
    Route::get('reports/participation/download', [ReportController::class, 'downloadParticipation'])->name('reports.participation.download');
    Route::get('reports/delegations/{delegation}/roster', [ReportController::class, 'delegationRoster'])->name('reports.roster');
    Route::get('reports/delegations/{delegation}/roster/download', [ReportController::class, 'downloadDelegationRoster'])->name('reports.roster.download');
    Route::get('reports/events/{event}/entries', [ReportController::class, 'eventEntries'])->name('reports.event-entries');
    Route::get('reports/events/{event}/entries/download', [ReportController::class, 'downloadEventEntries'])->name('reports.event-entries.download');
    Route::get('reports/results/{result}', [ReportController::class, 'resultSheet'])->name('reports.result-sheet');
    Route::get('reports/results/{result}/download', [ReportController::class, 'downloadResultSheet'])->name('reports.result-sheet.download');
    Route::get('reports/tally', [ReportController::class, 'tallyReport'])->name('reports.tally');
    Route::get('reports/tally/download', [ReportController::class, 'downloadTallyReport'])->name('reports.tally.download');
    Route::get('reports/medal-configuration', [ReportController::class, 'medalConfiguration'])->name('reports.medal-configuration');
    Route::get('reports/medal-configuration/download', [ReportController::class, 'downloadMedalConfiguration'])->name('reports.medal-configuration.download');
    Route::get('reports/schedule', [ReportController::class, 'scheduleSheet'])->name('reports.schedule');
    Route::get('reports/schedule/download', [ReportController::class, 'downloadScheduleSheet'])->name('reports.schedule.download');

    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->name('audit-logs.index');

    Route::get('management', [ManagementDashboardController::class, 'index'])
        ->middleware('can:view-management-reports')
        ->name('management.index');
    Route::get('reports/management', [ManagementDashboardController::class, 'report'])
        ->middleware('can:view-management-reports')
        ->name('reports.management');
    Route::get('reports/management/download', [ManagementDashboardController::class, 'downloadReport'])
        ->middleware('can:view-management-reports')
        ->name('reports.management.download');

    Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::patch('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::patch('announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');
    Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('content', [ContentManagementController::class, 'index'])->name('content.index');
    Route::get('content/news', [NewsController::class, 'index'])->name('content.news.index');
    Route::post('content/news', [NewsController::class, 'store'])->name('content.news.store');
    Route::put('content/news/{newsItem}', [NewsController::class, 'update'])->name('content.news.update');
    Route::patch('content/news/{newsItem}/status', [NewsController::class, 'status'])->name('content.news.status');
    Route::delete('content/news/{newsItem}', [NewsController::class, 'destroy'])->name('content.news.destroy');
    Route::get('content/faq', [FaqController::class, 'index'])->name('content.faq.index');
    Route::post('content/faq', [FaqController::class, 'store'])->name('content.faq.store');
    Route::put('content/faq/{faqItem}', [FaqController::class, 'update'])->name('content.faq.update');
    Route::patch('content/faq/{faqItem}/status', [FaqController::class, 'status'])->name('content.faq.status');
    Route::delete('content/faq/{faqItem}', [FaqController::class, 'destroy'])->name('content.faq.destroy');
    Route::get('content/gallery', [GalleryController::class, 'index'])->name('content.gallery.index');
    Route::post('content/gallery', [GalleryController::class, 'store'])->name('content.gallery.store');
    Route::patch('content/gallery/{galleryItem}/review', [GalleryController::class, 'review'])->name('content.gallery.review');
    Route::patch('content/gallery/publish', [GalleryController::class, 'publish'])->name('content.gallery.publish');
    Route::patch('content/gallery/{galleryItem}/unpublish', [GalleryController::class, 'unpublish'])->name('content.gallery.unpublish');
    Route::get('content/gallery/{galleryItem}/image', [GalleryController::class, 'image'])->name('content.gallery.image');

    Route::get('division', [DivisionController::class, 'edit'])
        ->middleware('can:administer')
        ->name('division.edit');
    Route::patch('division', [DivisionController::class, 'update'])
        ->middleware('can:administer')
        ->name('division.update');

    Route::get('system-settings', [SystemSettingsController::class, 'edit'])
        ->middleware('can:administer')
        ->name('system-settings.edit');
    Route::put('system-settings', [SystemSettingsController::class, 'update'])
        ->middleware('can:administer')
        ->name('system-settings.update');

    Route::get('account-provisions', [AccountProvisionController::class, 'index'])->name('account-provisions.index');
    Route::post('account-provisions/{accountProvision}/invite', [AccountProvisionController::class, 'invite'])->name('account-provisions.invite');
    Route::post('account-provisions/{accountProvision}/reset-password', [AccountProvisionController::class, 'resetPassword'])->name('account-provisions.reset-password');

    Route::middleware('role:admin')->group(function () {
        Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
        Route::put('districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
        Route::patch('districts/{district}/archive', [DistrictController::class, 'archive'])->name('districts.archive');
        Route::patch('districts/{district}/restore', [DistrictController::class, 'restore'])->name('districts.restore');
        Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');

        Route::post('school-districts', [SchoolDistrictController::class, 'store'])->name('school-districts.store');
        Route::put('school-districts/{schoolDistrict}', [SchoolDistrictController::class, 'update'])->name('school-districts.update');
        Route::patch('school-districts/{schoolDistrict}/archive', [SchoolDistrictController::class, 'archive'])->name('school-districts.archive');
        Route::patch('school-districts/{schoolDistrict}/restore', [SchoolDistrictController::class, 'restore'])->name('school-districts.restore');
        Route::delete('school-districts/{schoolDistrict}', [SchoolDistrictController::class, 'destroy'])->name('school-districts.destroy');

        Route::post('sports', [SportController::class, 'store'])->name('sports.store');
        Route::put('sports/{sport}', [SportController::class, 'update'])->name('sports.update');
        Route::patch('sports/{sport}/archive', [SportController::class, 'archive'])->name('sports.archive');
        Route::patch('sports/{sport}/restore', [SportController::class, 'restore'])->name('sports.restore');
        Route::put('sports/{sport}/technical-officials', [SportController::class, 'syncTechnicalOfficials'])->name('sports.technical-officials');
        Route::put('sports/{sport}/tournament-manager', [SportController::class, 'syncTournamentManager'])->name('sports.tournament-manager');
        Route::delete('sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

        Route::patch('events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
        Route::patch('events/{event}/restore', [EventController::class, 'restore'])->name('events.restore');

        Route::patch('venues/{venue}/archive', [VenueController::class, 'archive'])->name('venues.archive');
        Route::patch('venues/{venue}/restore', [VenueController::class, 'restore'])->name('venues.restore');
        Route::delete('venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy');

        Route::post('management-teams', [ManagementTeamController::class, 'store'])->name('management-teams.store');
        Route::put('management-teams/{managementTeam}', [ManagementTeamController::class, 'update'])->name('management-teams.update');
        Route::delete('management-teams/{managementTeam}', [ManagementTeamController::class, 'destroy'])->name('management-teams.destroy');

        Route::put('meets/{meet}', [MeetController::class, 'update'])->name('meets.update');
        Route::patch('meets/{meet}/status', [MeetController::class, 'updateStatus'])->name('meets.status');
        Route::patch('meets/{meet}/publish', [MeetController::class, 'publish'])->name('meets.publish');
        Route::patch('meets/{meet}/unpublish', [MeetController::class, 'unpublish'])->name('meets.unpublish');
        Route::patch('meets/{meet}/activate', [MeetController::class, 'activate'])->name('meets.activate');
        Route::patch('meets/{meet}/deactivate', [MeetController::class, 'deactivate'])->name('meets.deactivate');
        Route::put('meets/{meet}/events', [MeetController::class, 'syncEvents'])->name('meets.events');

        Route::post('delegations', [DelegationController::class, 'store'])->name('delegations.store');
        Route::delete('delegations/{delegation}', [DelegationController::class, 'destroy'])->name('delegations.destroy');

        Route::delete('accreditations/{accreditation}', [AccreditationController::class, 'destroy'])->name('accreditation.destroy');

        Route::patch('protests/{protest}/review', [ProtestController::class, 'review'])->name('protests.review');
        Route::patch('protests/{protest}/decide', [ProtestController::class, 'decide'])->name('protests.decide');

        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::put('incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::patch('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
        Route::patch('incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
        Route::delete('incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

    });

    Route::middleware('role:admin,organizer,technical_official,tournament_ict,tournament_secretary')->group(function () {
        Route::post('events', [EventController::class, 'store'])->name('events.store');
        Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::post('venues', [VenueController::class, 'store'])->name('venues.store');
        Route::put('venues/{venue}', [VenueController::class, 'update'])->name('venues.update');
    });

    // Schedule/Match management and result validate/correct/delete admit a
    // Tournament Manager, not just Admin/Organizer, unlike the flat
    // `role:admin,organizer` group above — each controller's own per-record
    // check (`ScopesToAssignedSport::userOperatesSport()`) then narrows a
    // Tournament Manager to their own managed sport only, the same
    // "coarse role gate here, precise scope in the controller" shape as the
    // live-scoring and result-encoding carve-outs already use.
    Route::middleware('role:admin,organizer,technical_official,tournament_manager,tournament_ict,tournament_secretary')->group(function () {
        Route::post('schedule', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::put('schedule/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

        Route::post('matches', [MatchController::class, 'store'])->name('matches.store');
        Route::put('matches/{match}', [MatchController::class, 'update'])->name('matches.update');
        Route::put('matches/{match}/participants', [MatchController::class, 'syncParticipants'])->name('matches.participants');
        Route::patch('matches/{match}/status', [MatchController::class, 'updateStatus'])->name('matches.status');
        Route::delete('matches/{match}', [MatchController::class, 'destroy'])->name('matches.destroy');

        Route::patch('results/{result}/validate', [ResultController::class, 'validateResult'])->name('results.validate');
        Route::patch('results/{result}/correct', [ResultController::class, 'correct'])->name('results.correct');
        Route::delete('results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
    });

    // Live scoring mutations are their own role group, not folded into the
    // block above: a Technical Official or Tournament Manager may run the
    // scoreboard but must not gain any of that block's other
    // meet-data-management permissions. An Organizer only gains scoreboard
    // access when specifically assigned as Tournament Secretary/ICT for the
    // match's meet+sport — a plain Organizer is still denied. Per-match/
    // session sport scoping (not just this coarse role check) happens
    // inside ScoringSessionController's own canManage().
    Route::middleware('role:admin,organizer,technical_official,tournament_manager,tournament_ict,tournament_secretary')->group(function () {
        Route::post('matches/{match}/scoring-sessions', [ScoringSessionController::class, 'store'])->name('scoring.start');
        Route::patch('scoring-sessions/{session}/score', [ScoringSessionController::class, 'score'])->name('scoring.score');
        Route::patch('scoring-sessions/{session}/period', [ScoringSessionController::class, 'period'])->name('scoring.period');
        Route::patch('scoring-sessions/{session}/pause', [ScoringSessionController::class, 'pause'])->name('scoring.pause');
        Route::patch('scoring-sessions/{session}/resume', [ScoringSessionController::class, 'resume'])->name('scoring.resume');
        Route::patch('scoring-sessions/{session}/end', [ScoringSessionController::class, 'end'])->name('scoring.end');
        Route::patch('scoring-sessions/{session}/foul', [ScoringSessionController::class, 'foul'])->name('scoring.foul');
        Route::delete('scoring-sessions/{session}/events/{event}', [ScoringSessionController::class, 'removeEvent'])->name('scoring.events.destroy');
        Route::patch('scoring-sessions/{session}/round', [ScoringSessionController::class, 'round'])->name('scoring.round');
        Route::patch('scoring-sessions/{session}/count', [ScoringSessionController::class, 'count'])->name('scoring.count');
        Route::patch('scoring-sessions/{session}/inning-run', [ScoringSessionController::class, 'inningRun'])->name('scoring.inning-run');

        // Basketball-only (WP live-basketball): roster/lineup, clocks,
        // possession, and the whistle/horn signals. Same role group as
        // every other scoring mutation above — canManage() inside each
        // controller narrows it further per match/session.
        Route::get('matches/{match}/roster', [MatchRosterController::class, 'show'])->name('match-roster.show');
        Route::post('matches/{match}/roster', [MatchRosterController::class, 'store'])->name('match-roster.store');
        Route::patch('match-roster/{rosterPlayer}', [MatchRosterController::class, 'update'])->name('match-roster.update');
        Route::delete('match-roster/{rosterPlayer}', [MatchRosterController::class, 'destroy'])->name('match-roster.destroy');
        Route::patch('scoring-sessions/{session}/settings', [ScoringSessionController::class, 'settings'])->name('scoring.settings');
        Route::patch('scoring-sessions/{session}/possession', [ScoringSessionController::class, 'possession'])->name('scoring.possession');
        Route::patch('scoring-sessions/{session}/game-clock', [ScoringSessionController::class, 'gameClock'])->name('scoring.game-clock');
        Route::patch('scoring-sessions/{session}/shot-clock', [ScoringSessionController::class, 'shotClock'])->name('scoring.shot-clock');
        Route::patch('scoring-sessions/{session}/horn', [ScoringSessionController::class, 'horn'])->name('scoring.horn');
        Route::patch('scoring-sessions/{session}/lineup', [ScoringSessionController::class, 'lineup'])->name('scoring.lineup');

        // Boxing-only: the round/rest countdown clock and the bell signal.
        Route::patch('scoring-sessions/{session}/round-clock', [ScoringSessionController::class, 'roundClock'])->name('scoring.round-clock');
        Route::patch('scoring-sessions/{session}/bell', [ScoringSessionController::class, 'bell'])->name('scoring.bell');

        // Volleyball/Sepak Takraw-only: rally-point scoring within a set.
        Route::patch('scoring-sessions/{session}/rally-point', [ScoringSessionController::class, 'rallyPoint'])->name('scoring.rally-point');

        // Football/Futsal-only: yellow/red card tallies.
        Route::patch('scoring-sessions/{session}/card', [ScoringSessionController::class, 'card'])->name('scoring.card');

        // Table Tennis/Badminton-only: point scoring within a game.
        Route::patch('scoring-sessions/{session}/game-point', [ScoringSessionController::class, 'gamePoint'])->name('scoring.game-point');

        // Wrestling-only: named-move point scoring, the period/rest clock,
        // and declaring/clearing a fall.
        Route::patch('scoring-sessions/{session}/wrestling-point', [ScoringSessionController::class, 'wrestlingPoint'])->name('scoring.wrestling-point');
        Route::patch('scoring-sessions/{session}/period-clock', [ScoringSessionController::class, 'periodClock'])->name('scoring.period-clock');
        Route::patch('scoring-sessions/{session}/fall', [ScoringSessionController::class, 'fall'])->name('scoring.fall');

        // Tennis-only: real Love/15/30/40/deuce/advantage point scoring
        // (games within a set, sets within a match) and a single-level
        // undo for the last point.
        Route::patch('scoring-sessions/{session}/tennis-point', [ScoringSessionController::class, 'tennisPoint'])->name('scoring.tennis-point');
        Route::patch('scoring-sessions/{session}/tennis-undo', [ScoringSessionController::class, 'tennisUndo'])->name('scoring.tennis-undo');

        // Goal ball only: penalty throw tally (the resulting goal, if
        // scored, is still recorded via the ordinary score() endpoint).
        Route::patch('scoring-sessions/{session}/penalty-throw', [ScoringSessionController::class, 'penaltyThrow'])->name('scoring.penalty-throw');

        // Billiard only: declare the winner of the rack just played, and
        // undo that declaration if it was a mistake.
        Route::patch('scoring-sessions/{session}/billiard-rack', [ScoringSessionController::class, 'billiardRack'])->name('scoring.billiard-rack');
        Route::patch('scoring-sessions/{session}/billiard-undo-rack', [ScoringSessionController::class, 'billiardUndoRack'])->name('scoring.billiard-undo-rack');

        // Bocce only: award a completed end's points to one side, and
        // undo that award if it was a mistake.
        Route::patch('scoring-sessions/{session}/bocce-end', [ScoringSessionController::class, 'bocceEnd'])->name('scoring.bocce-end');
        Route::patch('scoring-sessions/{session}/bocce-undo-end', [ScoringSessionController::class, 'bocceUndoEnd'])->name('scoring.bocce-undo-end');
    });

    // A Technical Official may encode a result directly for their own
    // sport (Phase 16) — validating, correcting, or deleting a result
    // stays a manager decision (the block above), so encode and validate
    // are deliberately two different route groups even though they're
    // both on ResultController, mirroring the existing encode≠validate
    // separation already built into that controller.
    Route::middleware('role:admin,organizer,technical_official,tournament_ict,tournament_secretary')->group(function () {
        Route::post('results', [ResultController::class, 'store'])->name('results.store');
        Route::put('results/{result}', [ResultController::class, 'update'])->name('results.update');
    });
});

require __DIR__.'/settings.php';
