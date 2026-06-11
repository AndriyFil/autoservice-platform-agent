<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardBookingRequestController;
use App\Http\Controllers\PublicBookingRequestController;
use App\Http\Controllers\WorkshopOnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])
    ->prefix('dashboard/booking-requests')
    ->name('dashboard.booking-requests.')
    ->group(function () {
        Route::get('{bookingRequest}', [DashboardBookingRequestController::class, 'show'])
            ->name('show');

        Route::patch('{bookingRequest}/status', [DashboardBookingRequestController::class, 'updateStatus'])
            ->name('status');
    });

Route::get('book/{workshop:slug}', [PublicBookingRequestController::class, 'create'])
    ->name('public-booking-requests.create');

Route::post('book/{workshop:slug}', [PublicBookingRequestController::class, 'store'])
    ->name('public-booking-requests.store');

Route::get('book/{workshop:slug}/success', [PublicBookingRequestController::class, 'success'])
    ->name('public-booking-requests.success');

Route::middleware(['auth'])->group(function () {
    Route::get('workshop-onboarding', [WorkshopOnboardingController::class, 'create'])
        ->name('workshop-onboarding.create');

    Route::post('workshop-onboarding', [WorkshopOnboardingController::class, 'store'])
        ->name('workshop-onboarding.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
