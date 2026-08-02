<?php

use App\Enums\SportPortalSlug;
use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\ManagementDashboardController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MeetController;
use App\Http\Controllers\MeetSportAssignmentController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\ProtestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolDistrictController;
use App\Http\Controllers\ScoringSessionController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\TallyController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

// Public portal — guest routes, throttled, published data only.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/', [PortalController::class, 'home'])->name('home');
    Route::get('districts/{district}/logo', [DistrictController::class, 'logo'])->name('districts.logo');
    Route::get('division/logo', [DivisionController::class, 'logo'])->name('division.logo');
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
        ->whereNumber(['meet', 'match'])
        ->name('public.scoreboard');
    Route::get('meets/{meet}/matches/{match}/scoreboard/poll', [PortalController::class, 'scoreboardPoll'])
        ->whereNumber(['meet', 'match'])
        ->name('public.scoreboard.poll');

    // Phase 12: permanent, meet-agnostic sport-portal routes
    // (`/basketball`, etc.) — constrained to the 12 known slugs so this
    // can never intercept any other top-level route.
    Route::get('{sportSlug}/poll', [PortalController::class, 'sportPortalPoll'])
        ->whereIn('sportSlug', SportPortalSlug::values())
        ->name('public.sport-portal.poll');
    Route::get('{sportSlug}', [PortalController::class, 'sportPortal'])
        ->whereIn('sportSlug', SportPortalSlug::values())
        ->name('public.sport-portal');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('uploads', [FileUploadController::class, 'store'])->name('uploads.store');
    Route::get('uploads/{upload}', [FileUploadController::class, 'download'])->name('uploads.download');
    Route::delete('uploads/{upload}', [FileUploadController::class, 'destroy'])->name('uploads.destroy');

    Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::get('school-districts', [SchoolDistrictController::class, 'index'])->name('school-districts.index');
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::get('sports', [SportController::class, 'index'])->name('sports.index');
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('meets', [MeetController::class, 'index'])->name('meets.index');
    Route::get('venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');
    Route::get('meet-sport-assignments', [MeetSportAssignmentController::class, 'index'])->name('meet-sport-assignments.index');

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
    Route::get('athletes/{athlete}', [AthleteController::class, 'show'])->name('athletes.show');
    Route::get('athletes/{athlete}/photo', [AthleteController::class, 'photo'])->name('athletes.photo');
    Route::get('athletes/{athlete}/sports-photo', [AthleteController::class, 'sportsPhoto'])->name('athletes.sports-photo');
    Route::post('athletes', [AthleteController::class, 'store'])->name('athletes.store');
    Route::put('athletes/{athlete}', [AthleteController::class, 'update'])->name('athletes.update');
    Route::delete('athletes/{athlete}', [AthleteController::class, 'destroy'])->name('athletes.destroy');

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

    Route::get('matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('matches/{match}/scoring-session', [ScoringSessionController::class, 'show'])->name('scoring.show');
    Route::get('matches/{match}/scoreboard', [ScoringSessionController::class, 'board'])->name('scoring.board');
    Route::get('results', [ResultController::class, 'index'])->name('results.index');
    Route::get('tally', [TallyController::class, 'index'])->name('tally.index');

    Route::get('protests', [ProtestController::class, 'index'])->name('protests.index');
    Route::post('protests', [ProtestController::class, 'store'])->name('protests.store');

    Route::get('eligibility', [EligibilityController::class, 'index'])->name('eligibility.index');
    Route::post('eligibility/documents', [EligibilityController::class, 'storeDocument'])->name('eligibility.documents.store');
    Route::get('eligibility/documents/{document}', [EligibilityController::class, 'downloadDocument'])->name('eligibility.documents.download');
    Route::delete('eligibility/documents/{document}', [EligibilityController::class, 'destroyDocument'])->name('eligibility.documents.destroy');
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
    Route::get('reports/schedule', [ReportController::class, 'scheduleSheet'])->name('reports.schedule');
    Route::get('reports/schedule/download', [ReportController::class, 'downloadScheduleSheet'])->name('reports.schedule.download');

    Route::get('audit-logs', [AuditLogController::class, 'index'])
        ->middleware('can:administer')
        ->name('audit-logs.index');

    Route::get('management', [ManagementDashboardController::class, 'index'])
        ->middleware('can:manage-meet-data')
        ->name('management.index');
    Route::get('reports/management', [ManagementDashboardController::class, 'report'])
        ->middleware('can:manage-meet-data')
        ->name('reports.management');
    Route::get('reports/management/download', [ManagementDashboardController::class, 'downloadReport'])
        ->middleware('can:manage-meet-data')
        ->name('reports.management.download');

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

    Route::middleware('role:admin,organizer')->group(function () {
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

        Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
        Route::put('schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
        Route::patch('schools/{school}/archive', [SchoolController::class, 'archive'])->name('schools.archive');
        Route::patch('schools/{school}/restore', [SchoolController::class, 'restore'])->name('schools.restore');
        Route::delete('schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');

        Route::post('sports', [SportController::class, 'store'])->name('sports.store');
        Route::put('sports/{sport}', [SportController::class, 'update'])->name('sports.update');
        Route::patch('sports/{sport}/archive', [SportController::class, 'archive'])->name('sports.archive');
        Route::patch('sports/{sport}/restore', [SportController::class, 'restore'])->name('sports.restore');
        Route::put('sports/{sport}/technical-officials', [SportController::class, 'syncTechnicalOfficials'])->name('sports.technical-officials');
        Route::delete('sports/{sport}', [SportController::class, 'destroy'])->name('sports.destroy');

        Route::post('events', [EventController::class, 'store'])->name('events.store');
        Route::put('events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::patch('events/{event}/archive', [EventController::class, 'archive'])->name('events.archive');
        Route::patch('events/{event}/restore', [EventController::class, 'restore'])->name('events.restore');
        Route::delete('events/{event}', [EventController::class, 'destroy'])->name('events.destroy');

        Route::post('venues', [VenueController::class, 'store'])->name('venues.store');
        Route::put('venues/{venue}', [VenueController::class, 'update'])->name('venues.update');
        Route::patch('venues/{venue}/archive', [VenueController::class, 'archive'])->name('venues.archive');
        Route::patch('venues/{venue}/restore', [VenueController::class, 'restore'])->name('venues.restore');
        Route::delete('venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy');

        Route::post('schedule', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::put('schedule/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('schedule/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');

        Route::post('meet-sport-assignments', [MeetSportAssignmentController::class, 'store'])->name('meet-sport-assignments.store');
        Route::patch('meet-sport-assignments/{meetSportAssignment}/status', [MeetSportAssignmentController::class, 'updateStatus'])->name('meet-sport-assignments.status');
        Route::delete('meet-sport-assignments/{meetSportAssignment}', [MeetSportAssignmentController::class, 'destroy'])->name('meet-sport-assignments.destroy');

        Route::post('meets', [MeetController::class, 'store'])->name('meets.store');
        Route::put('meets/{meet}', [MeetController::class, 'update'])->name('meets.update');
        Route::patch('meets/{meet}/status', [MeetController::class, 'updateStatus'])->name('meets.status');
        Route::patch('meets/{meet}/publish', [MeetController::class, 'publish'])->name('meets.publish');
        Route::patch('meets/{meet}/unpublish', [MeetController::class, 'unpublish'])->name('meets.unpublish');
        Route::patch('meets/{meet}/activate', [MeetController::class, 'activate'])->name('meets.activate');
        Route::patch('meets/{meet}/deactivate', [MeetController::class, 'deactivate'])->name('meets.deactivate');
        Route::put('meets/{meet}/events', [MeetController::class, 'syncEvents'])->name('meets.events');
        Route::delete('meets/{meet}', [MeetController::class, 'destroy'])->name('meets.destroy');

        Route::post('delegations', [DelegationController::class, 'store'])->name('delegations.store');
        Route::delete('delegations/{delegation}', [DelegationController::class, 'destroy'])->name('delegations.destroy');

        Route::post('accreditations', [AccreditationController::class, 'store'])->name('accreditation.store');
        Route::delete('accreditations/{accreditation}', [AccreditationController::class, 'destroy'])->name('accreditation.destroy');

        Route::post('matches', [MatchController::class, 'store'])->name('matches.store');
        Route::put('matches/{match}', [MatchController::class, 'update'])->name('matches.update');
        Route::put('matches/{match}/participants', [MatchController::class, 'syncParticipants'])->name('matches.participants');
        Route::patch('matches/{match}/status', [MatchController::class, 'updateStatus'])->name('matches.status');
        Route::delete('matches/{match}', [MatchController::class, 'destroy'])->name('matches.destroy');

        Route::patch('protests/{protest}/review', [ProtestController::class, 'review'])->name('protests.review');
        Route::patch('protests/{protest}/decide', [ProtestController::class, 'decide'])->name('protests.decide');

        Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::patch('announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
        Route::patch('announcements/{announcement}/unpublish', [AnnouncementController::class, 'unpublish'])->name('announcements.unpublish');
        Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::put('incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::patch('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
        Route::patch('incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
        Route::delete('incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

        Route::patch('results/{result}/validate', [ResultController::class, 'validateResult'])->name('results.validate');
        Route::patch('results/{result}/correct', [ResultController::class, 'correct'])->name('results.correct');
        Route::delete('results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
    });

    // Live scoring mutations are their own role group, not folded into the
    // block above: a Technical Official may run the scoreboard but must not
    // gain any of that block's other meet-data-management permissions.
    // Organizer is deliberately excluded here — only Admin and Technical
    // Official may manage/operate the live scoreboard.
    // Per-match/session sport scoping (not just this coarse role check)
    // happens inside ScoringSessionController itself.
    Route::middleware('role:admin,technical_official')->group(function () {
        Route::post('matches/{match}/scoring-sessions', [ScoringSessionController::class, 'store'])->name('scoring.start');
        Route::patch('scoring-sessions/{session}/score', [ScoringSessionController::class, 'score'])->name('scoring.score');
        Route::patch('scoring-sessions/{session}/period', [ScoringSessionController::class, 'period'])->name('scoring.period');
        Route::patch('scoring-sessions/{session}/pause', [ScoringSessionController::class, 'pause'])->name('scoring.pause');
        Route::patch('scoring-sessions/{session}/resume', [ScoringSessionController::class, 'resume'])->name('scoring.resume');
        Route::patch('scoring-sessions/{session}/end', [ScoringSessionController::class, 'end'])->name('scoring.end');
        Route::patch('scoring-sessions/{session}/foul', [ScoringSessionController::class, 'foul'])->name('scoring.foul');
        Route::patch('scoring-sessions/{session}/round', [ScoringSessionController::class, 'round'])->name('scoring.round');
        Route::patch('scoring-sessions/{session}/count', [ScoringSessionController::class, 'count'])->name('scoring.count');
        Route::patch('scoring-sessions/{session}/inning-run', [ScoringSessionController::class, 'inningRun'])->name('scoring.inning-run');
    });

    // A Technical Official may encode a result directly for their own
    // sport (Phase 16) — validating, correcting, or deleting a result
    // stays a manager decision (the block above), so encode and validate
    // are deliberately two different route groups even though they're
    // both on ResultController, mirroring the existing encode≠validate
    // separation already built into that controller.
    Route::middleware('role:admin,organizer,technical_official')->group(function () {
        Route::post('results', [ResultController::class, 'store'])->name('results.store');
        Route::put('results/{result}', [ResultController::class, 'update'])->name('results.update');
    });
});

require __DIR__.'/settings.php';
