<?php

use Illuminate\Support\Facades\Route;
use Prasso\Church\Livewire\MemberDashboard;
use Prasso\Church\Http\Controllers\CleaningSignupController;



// Printable Prayer Requests
// These routes are used by Filament header actions in
// `Prasso\Church\Filament\Resources\PrayerRequestResource\Pages\ListPrayerRequests`
// and must be named with the `church.` prefix.
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/prayer-requests/print-all', [\Prasso\Church\Http\Controllers\PrayerPrintController::class, 'printAll'])
        ->name('church.prayer-requests.print-all');

    Route::get('/prayer-requests/print-sms', [\Prasso\Church\Http\Controllers\PrayerPrintController::class, 'printSms'])
        ->name('church.prayer-requests.print-sms');

    // Member Dashboard (CHM) - Available to all authenticated users who are members
    Route::get('/member', function () {
        return view('church::member-dashboard');
    })->name('church.member.dashboard');

    Route::get('/cleaning-signup/report', [CleaningSignupController::class, 'report'])
        ->name('church.cleaning.signup.report');
});

// Cleaning Signup Routes - Available to both authenticated and unauthenticated users
Route::middleware(['web'])->group(function () {
    Route::get('/cleaning-signup', [CleaningSignupController::class, 'show'])
        ->name('church.cleaning.signup.show');
    Route::post('/cleaning-signup', [CleaningSignupController::class, 'store'])
        ->name('church.cleaning.signup.store');
    Route::get('/cleaning-signup/schedule', [CleaningSignupController::class, 'getSchedule'])
        ->name('church.cleaning.signup.schedule');
});
