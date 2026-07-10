<?php

namespace App\Domain\BookingRequests\Actions;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Shared\ValueObjects\Phone;
use App\Models\BookingRequest;
use App\Models\Workshop;

class CreatePublicBookingRequestAction
{
    /**
     * @param  array{
     *     customer_name: string,
     *     customer_phone: string,
     *     problem_description: string,
     *     preferred_date?: string|null,
     *     vehicle?: array{brand?: string|null, model?: string|null, license_plate?: string|null}
     * }  $data
     */
    public function handle(Workshop $workshop, array $data): BookingRequest
    {
        $customerPhone = $data['customer_phone'];
        $problemDescription = $data['problem_description'];

        return BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => $data['customer_name'],
            'customer_phone' => $customerPhone,
            'customer_phone_normalized' => (new Phone($customerPhone))->normalize(),
            'problem_description' => $problemDescription,
            'original_message' => $problemDescription,
            'preferred_date' => $data['preferred_date'] ?? null,
            'status' => BookingRequestStatus::New,
        ]);
    }
}
