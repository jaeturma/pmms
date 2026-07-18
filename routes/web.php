<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DistrictController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\MeetController;
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
    });
});

require __DIR__.'/settings.php';
