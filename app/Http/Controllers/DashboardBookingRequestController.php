<?php

namespace App\Http\Controllers;

use App\Actions\BookingRequests\ChangeBookingRequestStatusAction;
use App\Actions\BookingRequests\CreateDashboardBookingRequestAction;
use App\Http\Requests\SearchDashboardCustomersRequest;
use App\Http\Requests\StoreDashboardBookingRequestRequest;
use App\Http\Requests\UpdateDashboardBookingRequestStatusRequest;
use App\Models\BookingRequest;
use App\Queries\Customers\SearchActiveWorkshopCustomersQuery;
use App\Queries\Dashboard\DashboardBookingRequestDetailsQuery;
use App\Support\ActiveWorkshopMembershipResolver;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardBookingRequestController extends Controller
{
    public function create(
        Request $request,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
    ): Response|RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard/BookingRequests/Create', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
        ]);
    }

    public function store(
        StoreDashboardBookingRequestRequest $request,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        CreateDashboardBookingRequestAction $createDashboardBookingRequest,
    ): RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        $bookingRequest = $createDashboardBookingRequest->handle($activeWorkshopUser, $request->validated());

        return to_route('dashboard.booking-requests.show', $bookingRequest)
            ->with('status', 'Booking request created.');
    }

    public function searchCustomers(
        SearchDashboardCustomersRequest $request,
        ActiveWorkshopMembershipResolver $activeWorkshopMembershipResolver,
        SearchActiveWorkshopCustomersQuery $customersQuery,
    ): JsonResponse|RedirectResponse {
        $activeWorkshopUser = $activeWorkshopMembershipResolver->resolve($request->user(), $request->session());

        if (! $activeWorkshopUser) {
            return to_route('workshop-onboarding.create');
        }

        return response()->json($customersQuery->handle($activeWorkshopUser, $request->queryText()));
    }

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
