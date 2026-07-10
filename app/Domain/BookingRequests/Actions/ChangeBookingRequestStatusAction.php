<?php

namespace App\Domain\BookingRequests\Actions;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\WorkshopUser;
use DomainException;

class ChangeBookingRequestStatusAction
{
    public function handle(
        WorkshopUser $activeWorkshopUser,
        BookingRequest $bookingRequest,
        BookingRequestStatus $targetStatus,
    ): BookingRequest {
        $bookingRequest = BookingRequest::query()
            ->whereKey($bookingRequest->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        if (! $bookingRequest->status->canTransitionTo($targetStatus)) {
            throw new DomainException('This booking request cannot move to the selected status.');
        }

        $bookingRequest->status = $targetStatus;
        $bookingRequest->save();

        return $bookingRequest->refresh();
    }
}
