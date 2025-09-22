<?php

use Illuminate\Support\Facades\Route;
use Prasso\Church\Http\Controllers\PrayerRequestPrintController;

// Prayer Request Print Routes
Route::name('church.')->group(function () {
    Route::prefix('prayer-requests')->name('prayer-requests.')->group(function () {
        Route::get('print/{id}', [PrayerRequestPrintController::class, 'printSingle'])->name('print');
        Route::get('print-multiple', [PrayerRequestPrintController::class, 'printMultiple'])->name('print-multiple');
        Route::get('print-all', [PrayerRequestPrintController::class, 'printAll'])->name('print-all');
        Route::get('print-sms', [PrayerRequestPrintController::class, 'printSmsRequests'])->name('print-sms');
    });
});
