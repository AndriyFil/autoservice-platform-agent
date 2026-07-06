<?php

namespace App\Actions\BookingRequests;

use App\Models\Customer;
use App\Models\Workshop;
use App\Support\Phone;

class ResolveBookingRequestCustomerAction
{
    public function handle(Workshop $workshop, string $name, string $phone, ?int $customerId = null): Customer
    {
        if ($customerId !== null) {
            return Customer::query()
                ->whereKey($customerId)
                ->where('workshop_id', $workshop->id)
                ->firstOrFail();
        }

        return Customer::firstOrCreate(
            [
                'workshop_id' => $workshop->id,
                'phone_normalized' => (new Phone($phone))->normalize(),
            ],
            [
                'name' => $name,
                'phone' => $phone,
                'normalized_phone' => (new Phone($phone))->normalizeLegacyDigits(),
            ],
        );
    }
}
