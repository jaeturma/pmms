<?php

use App\Http\Controllers\AccreditationController;
use App\Http\Controllers\AthleteController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MeetController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\ProtestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\TallyController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('uploads', [FileUploadController::class, 'store'])->name('uploads.store');
    Route::get('uploads/{upload}', [FileUploadController::class, 'download'])->name('uploads.download');
    Route::delete('uploads/{upload}', [FileUploadController::class, 'destroy'])->name('uploads.destroy');

    Route::get('districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
    Route::get('sports', [SportController::class, 'index'])->name('sports.index');
    Route::get('events', [EventController::class, 'index'])->name('events.index');
    Route::get('meets', [MeetController::class, 'index'])->name('meets.index');
    Route::get('venues', [VenueController::class, 'index'])->name('venues.index');
    Route::get('schedule', [ScheduleController::class, 'index'])->name('schedule.index');

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

    Route::middleware('role:admin,organizer')->group(function () {
        Route::post('districts', [DistrictController::class, 'store'])->name('districts.store');
        Route::put('districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
        Route::patch('districts/{district}/archive', [DistrictController::class, 'archive'])->name('districts.archive');
        Route::patch('districts/{district}/restore', [DistrictController::class, 'restore'])->name('districts.restore');
        Route::delete('districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');

        Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
        Route::put('schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
        Route::patch('schools/{school}/archive', [SchoolController::class, 'archive'])->name('schools.archive');
        Route::patch('schools/{school}/restore', [SchoolController::class, 'restore'])->name('schools.restore');
        Route::delete('schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');

        Route::post('sports', [SportController::class, 'store'])->name('sports.store');
        Route::put('sports/{sport}', [SportController::class, 'update'])->name('sports.update');
        Route::patch('sports/{sport}/archive', [SportController::class, 'archive'])->name('sports.archive');
        Route::patch('sports/{sport}/restore', [SportController::class, 'restore'])->name('sports.restore');
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

        Route::post('meets', [MeetController::class, 'store'])->name('meets.store');
        Route::put('meets/{meet}', [MeetController::class, 'update'])->name('meets.update');
        Route::patch('meets/{meet}/status', [MeetController::class, 'updateStatus'])->name('meets.status');
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

        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::post('incidents', [IncidentController::class, 'store'])->name('incidents.store');
        Route::put('incidents/{incident}', [IncidentController::class, 'update'])->name('incidents.update');
        Route::patch('incidents/{incident}/resolve', [IncidentController::class, 'resolve'])->name('incidents.resolve');
        Route::patch('incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
        Route::delete('incidents/{incident}', [IncidentController::class, 'destroy'])->name('incidents.destroy');

        Route::post('results', [ResultController::class, 'store'])->name('results.store');
        Route::put('results/{result}', [ResultController::class, 'update'])->name('results.update');
        Route::patch('results/{result}/validate', [ResultController::class, 'validateResult'])->name('results.validate');
        Route::patch('results/{result}/correct', [ResultController::class, 'correct'])->name('results.correct');
        Route::delete('results/{result}', [ResultController::class, 'destroy'])->name('results.destroy');
    });
});

require __DIR__.'/settings.php';
