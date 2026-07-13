<?php

namespace App\Domain\BookingRequests\Actions;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class OpenRepairOrderCreateFromBookingRequestAction
{
    public function handle(WorkshopUser $activeWorkshopUser, int $bookingRequestId): BookingRequest
    {
        return DB::transaction(function () use ($activeWorkshopUser, $bookingRequestId): BookingRequest {
            $bookingRequest = BookingRequest::query()
                ->with('repairOrder')
                ->whereKey($bookingRequestId)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bookingRequest->repairOrder) {
                return $bookingRequest;
            }

            if (! in_array($bookingRequest->status, [BookingRequestStatus::New, BookingRequestStatus::Confirmed], true)) {
                throw new DomainException('Repair order can be created only from a new or confirmed booking request.');
            }

            if ($bookingRequest->status === BookingRequestStatus::New) {
                $bookingRequest->status = BookingRequestStatus::Confirmed;
                $bookingRequest->save();
            }

            return $bookingRequest->refresh()->load('repairOrder');
        });
    }
}
