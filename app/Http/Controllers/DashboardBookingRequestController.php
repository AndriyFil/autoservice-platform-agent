<?php

namespace App\Http\Controllers;

use App\Actions\BookingRequests\ChangeBookingRequestStatusAction;
use App\Http\Requests\UpdateDashboardBookingRequestStatusRequest;
use App\Models\BookingRequest;
use App\Queries\Dashboard\DashboardBookingRequestDetailsQuery;
use App\Support\ActiveWorkshopMembershipResolver;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardBookingRequestController extends Controller
{
    public function show(
        Request $request,
        BookingRequest $bookingRequest,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        DashboardBookingRequestDetailsQuery $bookingRequestDetailsQuery,
    ): Response|RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard/BookingRequests/Show', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'bookingRequest' => $bookingRequestDetailsQuery->handle($activeWorkshopUser, $bookingRequest),
        ]);
    }

    public function updateStatus(
        UpdateDashboardBookingRequestStatusRequest $request,
        BookingRequest $bookingRequest,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        ChangeBookingRequestStatusAction $changeBookingRequestStatus,
    ): RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        try {
            $changeBookingRequestStatus->handle($activeWorkshopUser, $bookingRequest, $request->status());
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()
            ->with('status', 'Booking request status updated.');
    }
}
