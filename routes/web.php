<?php

use App\Http\Controllers\CancelDashboardRepairOrderController;
use App\Http\Controllers\CompleteDashboardRepairOrderController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardBookingRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardDocumentDownloadController;
use App\Http\Controllers\DashboardRepairOrderController;
use App\Http\Controllers\DashboardRepairOrderLineController;
use App\Http\Controllers\EstimateDashboardRepairOrderController;
use App\Http\Controllers\PublicBookingRequestController;
use App\Http\Controllers\PublicIntakeController;
use App\Http\Controllers\StartDashboardRepairOrderController;
use App\Http\Controllers\WorkshopOnboardingController;
use App\Http\Middleware\EnsureActiveWorkshop;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::get('w/{workshop:slug}', [PublicIntakeController::class, 'create'])
    ->name('public-intake.create');

Route::post('w/{workshop:slug}/intake', [PublicIntakeController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public-intake.store');

Route::get('dashboard', [DashboardController::class, 'show'])
    ->middleware(['auth', EnsureActiveWorkshop::class])
    ->name('dashboard');

Route::middleware(['auth', EnsureActiveWorkshop::class])
    ->prefix('dashboard/booking-requests')
    ->name('dashboard.booking-requests.')
    ->group(function () {
        Route::get('{bookingRequest}', [DashboardBookingRequestController::class, 'show'])
            ->name('show');

        Route::patch('{bookingRequest}/status', [DashboardBookingRequestController::class, 'updateStatus'])
            ->name('status');
    });

Route::middleware(['auth', EnsureActiveWorkshop::class])
    ->prefix('dashboard/repair-orders')
    ->name('dashboard.repair-orders.')
    ->group(function () {
        Route::get('/', [DashboardRepairOrderController::class, 'index'])
            ->name('index');

        Route::get('create', [DashboardRepairOrderController::class, 'create'])
            ->name('create');

        Route::post('/', [DashboardRepairOrderController::class, 'store'])
            ->name('store');

        Route::get('{repairOrder}', [DashboardRepairOrderController::class, 'show'])
            ->name('show');

        Route::post('{repairOrder}/lines', [DashboardRepairOrderLineController::class, 'store'])
            ->name('lines.store');

        Route::patch('{repairOrder}/lines/{repairOrderLine}', [DashboardRepairOrderLineController::class, 'update'])
            ->name('lines.update');

        Route::delete('{repairOrder}/lines/{repairOrderLine}', [DashboardRepairOrderLineController::class, 'destroy'])
            ->name('lines.destroy');

        Route::post('{repairOrder}/estimate', [EstimateDashboardRepairOrderController::class, 'store'])
            ->name('estimate');

        Route::post('{repairOrder}/start', [StartDashboardRepairOrderController::class, 'store'])
            ->name('start');

        Route::post('{repairOrder}/complete', [CompleteDashboardRepairOrderController::class, 'store'])
            ->name('complete');

        Route::post('{repairOrder}/cancel', [CancelDashboardRepairOrderController::class, 'store'])
            ->name('cancel');
    });

Route::middleware(['auth', EnsureActiveWorkshop::class])
    ->prefix('dashboard/documents')
    ->name('dashboard.documents.')
    ->group(function () {
        Route::get('{document}/download', [DashboardDocumentDownloadController::class, 'show'])
            ->name('download');
    });

Route::middleware(['auth', EnsureActiveWorkshop::class])
    ->prefix('customers')
    ->name('customers.')
    ->group(function () {
        Route::get('/', [CustomerController::class, 'index'])
            ->name('index');

        Route::get('{customer}', [CustomerController::class, 'show'])
            ->name('show');
    });

Route::get('book/{workshop:slug}', [PublicBookingRequestController::class, 'create'])
    ->name('public-booking-requests.create');

Route::post('book/{workshop:slug}', [PublicBookingRequestController::class, 'store'])
    ->middleware('throttle:10,1')
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
