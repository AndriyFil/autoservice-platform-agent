<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkshopOnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('workshop-onboarding', [WorkshopOnboardingController::class, 'create'])
        ->name('workshop-onboarding.create');

    Route::post('workshop-onboarding', [WorkshopOnboardingController::class, 'store'])
        ->name('workshop-onboarding.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
