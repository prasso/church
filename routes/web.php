<?php

use Illuminate\Support\Facades\Route;
use Prasso\Church\Livewire\MemberDashboard;



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
        return app(MemberDashboard::class);
    })->name('church.member.dashboard');
});
