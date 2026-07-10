<?php

namespace App\Domain\BookingRequests\Services;

use App\Domain\Shared\ValueObjects\Phone;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Workshop;
use App\Models\WorkshopUser;

class CustomerMatcher
{
    public function matchBookingRequest(WorkshopUser $activeWorkshopUser, BookingRequest $bookingRequest): ?Customer
    {
        return $this->matchByWorkshopAndPhone(
            $activeWorkshopUser->workshop,
            $bookingRequest->customer_phone_normalized ?: $bookingRequest->customer_phone,
        );
    }

    private function matchByWorkshopAndPhone(Workshop $workshop, ?string $phone): ?Customer
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        return Customer::query()
            ->with(['vehicles' => fn ($query) => $query
                ->orderBy('brand')
                ->orderBy('model')
                ->orderBy('id'),
            ])
            ->where('workshop_id', $workshop->id)
            ->where('phone_normalized', (new Phone($phone))->normalize())
            ->first();
    }
}
