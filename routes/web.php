<?php

use App\Http\Controllers\AthleteController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\EligibilityController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\MeetController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SportController;
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

    Route::get('delegations', [DelegationController::class, 'index'])->name('delegations.index');
    Route::put('delegations/{delegation}', [DelegationController::class, 'update'])->name('delegations.update');
    Route::patch('delegations/{delegation}/submit', [DelegationController::class, 'submit'])->name('delegations.submit');
    Route::patch('delegations/{delegation}/approve', [DelegationController::class, 'approve'])->name('delegations.approve');
    Route::patch('delegations/{delegation}/return', [DelegationController::class, 'returnToDraft'])->name('delegations.return');
    Route::put('delegations/{delegation}/officers', [DelegationController::class, 'syncOfficers'])->name('delegations.officers');

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

        Route::post('meets', [MeetController::class, 'store'])->name('meets.store');
        Route::put('meets/{meet}', [MeetController::class, 'update'])->name('meets.update');
        Route::patch('meets/{meet}/status', [MeetController::class, 'updateStatus'])->name('meets.status');
        Route::put('meets/{meet}/events', [MeetController::class, 'syncEvents'])->name('meets.events');
        Route::delete('meets/{meet}', [MeetController::class, 'destroy'])->name('meets.destroy');

        Route::post('delegations', [DelegationController::class, 'store'])->name('delegations.store');
        Route::delete('delegations/{delegation}', [DelegationController::class, 'destroy'])->name('delegations.destroy');
    });
});

require __DIR__.'/settings.php';
