<?php

namespace App\Http\Controllers;

use App\Domain\BookingRequests\Actions\ChangeBookingRequestStatusAction;
use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\BookingRequests\Queries\BookingRequestShowQuery;
use App\Http\Requests\UpdateDashboardBookingRequestStatusRequest;
use App\Models\BookingRequest;
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
        BookingRequestShowQuery $bookingRequestDetailsQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard/BookingRequests/Show', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            ...$bookingRequestDetailsQuery->handle($activeWorkshopUser, $bookingRequest),
        ]);
    }

    public function updateStatus(
        UpdateDashboardBookingRequestStatusRequest $request,
        BookingRequest $bookingRequest,
        ChangeBookingRequestStatusAction $changeBookingRequestStatus,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        try {
            $bookingRequest = $changeBookingRequestStatus->handle($activeWorkshopUser, $bookingRequest, $request->status());
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        if ($request->status() === BookingRequestStatus::Confirmed) {
            $repairOrder = $bookingRequest->repairOrder;

            if ($repairOrder) {
                return to_route('dashboard.repair-orders.show', $repairOrder);
            }

            return to_route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ])->with('status', 'Booking request confirmed. Complete the repair order to start work.');
        }

        return back()
            ->with('status', 'Booking request status updated.');
    }
}
