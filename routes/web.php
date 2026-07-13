<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPortalAccessController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\Dashboard\WorkshopSettingsController;
use App\Http\Controllers\Dashboard\WorkshopStaffController;
use App\Http\Controllers\DashboardBookingRequestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardDocumentDownloadController;
use App\Http\Controllers\DashboardRepairOrderController;
use App\Http\Controllers\DashboardRepairOrderEstimateApprovalRequirementController;
use App\Http\Controllers\DashboardRepairOrderLineController;
use App\Http\Controllers\EstimateDashboardRepairOrderController;
use App\Http\Controllers\PublicBookingRequestController;
use App\Http\Controllers\PublicHomeController;
use App\Http\Controllers\PublicIntakeController;
use App\Http\Controllers\WorkshopOnboardingController;
use App\Http\Middleware\EnsureActiveWorkshop;
use App\Http\Middleware\EnsureVerifiedCustomerPhone;
use App\Http\Middleware\RedirectIfCustomerPhoneVerified;
use App\Support\Urls\AppUrl;
use Illuminate\Support\Facades\Route;

$registerPublicSurface = static function (): void {
    // Public surface: marketing homepage and workshop customer intake pages.
    Route::get('/', PublicHomeController::class)->name('home');

    Route::get('my-requests/access', [CustomerPortalAccessController::class, 'create'])
        ->middleware(RedirectIfCustomerPhoneVerified::class)
        ->name('customer-portal.access.create');

    Route::post('my-requests/access', [CustomerPortalAccessController::class, 'store'])
        ->middleware([
            RedirectIfCustomerPhoneVerified::class,
            'throttle:customer-portal-code-request',
        ])
        ->name('customer-portal.access.store');

    Route::get('my-requests/verify', [CustomerPortalAccessController::class, 'verifyCreate'])
        ->name('customer-portal.verify.create');

    Route::post('my-requests/verify', [CustomerPortalAccessController::class, 'verifyStore'])
        ->middleware('throttle:customer-portal-code-verification')
        ->name('customer-portal.verify.store');

    Route::get('my-requests', CustomerPortalController::class)
        ->middleware(EnsureVerifiedCustomerPhone::class)
        ->name('customer-portal.index');

    Route::get('w/{workshop:slug}', [PublicIntakeController::class, 'create'])
        ->name('public-intake.create');

    Route::post('w/{workshop:slug}/intake', [PublicIntakeController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('public-intake.store');

    // Public surface: legacy workshop booking request routes kept for compatibility.
    Route::get('book/{workshop:slug}', [PublicBookingRequestController::class, 'create'])
        ->name('public-booking-requests.create');

    Route::post('book/{workshop:slug}', [PublicBookingRequestController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('public-booking-requests.store');

    Route::get('book/{workshop:slug}/success', [PublicBookingRequestController::class, 'success'])
        ->name('public-booking-requests.success');
};

$registerAdminSurface = static function (): void {
    // Dashboard/admin surface: owner and staff workspace.
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

            Route::patch('{repairOrder}/estimate-approval-requirement', [DashboardRepairOrderEstimateApprovalRequirementController::class, 'update'])
                ->name('estimate-approval-requirement.update');

            Route::patch('{repairOrder}/status', [DashboardRepairOrderController::class, 'updateStatus'])
                ->name('status');
        });

    Route::middleware(['auth', EnsureActiveWorkshop::class])
        ->prefix('dashboard/documents')
        ->name('dashboard.documents.')
        ->group(function () {
            Route::get('{document}/download', [DashboardDocumentDownloadController::class, 'show'])
                ->name('download');
        });

    Route::middleware(['auth', EnsureActiveWorkshop::class])
        ->prefix('dashboard/customers')
        ->name('customers.')
        ->group(function () {
            Route::get('/', [CustomerController::class, 'index'])
                ->name('index');

            Route::get('{customer}', [CustomerController::class, 'show'])
                ->name('show');

            Route::patch('{customer}', [CustomerController::class, 'update'])
                ->name('update');

            Route::post('{customer}/vehicles', [CustomerController::class, 'storeVehicle'])
                ->name('vehicles.store');

            Route::patch('{customer}/vehicles/{vehicle}', [CustomerController::class, 'updateVehicle'])
                ->name('vehicles.update');
        });

    Route::middleware(['auth', EnsureActiveWorkshop::class])
        ->prefix('dashboard/workshop')
        ->name('dashboard.workshop.')
        ->group(function () {
            Route::get('settings', [WorkshopSettingsController::class, 'show'])
                ->name('settings.show');

            Route::patch('settings', [WorkshopSettingsController::class, 'update'])
                ->name('settings.update');

            Route::post('staff', [WorkshopStaffController::class, 'store'])
                ->name('staff.store');

            Route::patch('staff/{workshopUser}', [WorkshopStaffController::class, 'update'])
                ->name('staff.update');

            Route::delete('staff/{workshopUser}', [WorkshopStaffController::class, 'destroy'])
                ->name('staff.destroy');
        });

    // Admin onboarding surface: authenticated workshop setup.
    Route::middleware(['auth'])->group(function () {
        Route::get('workshop-onboarding', [WorkshopOnboardingController::class, 'create'])
            ->name('workshop-onboarding.create');

        Route::post('workshop-onboarding', [WorkshopOnboardingController::class, 'store'])
            ->name('workshop-onboarding.store');
    });

    require __DIR__.'/settings.php';
    require __DIR__.'/auth.php';
};

if (AppUrl::hostsAreSplit()) {
    Route::domain(AppUrl::publicHost())->group($registerPublicSurface);
    Route::domain(AppUrl::adminHost())->group($registerAdminSurface);
} else {
    $registerPublicSurface();
    $registerAdminSurface();
}
