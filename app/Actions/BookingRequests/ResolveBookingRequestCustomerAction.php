<?php

namespace App\Actions\BookingRequests;

use App\Models\Customer;
use App\Models\Workshop;
use App\Support\PhoneNormalizer;

class ResolveBookingRequestCustomerAction
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer,
    ) {}

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
                'normalized_phone' => $this->phoneNormalizer->normalize($phone),
            ],
            [
                'name' => $name,
                'phone' => $phone,
            ],
        );
    }
}
