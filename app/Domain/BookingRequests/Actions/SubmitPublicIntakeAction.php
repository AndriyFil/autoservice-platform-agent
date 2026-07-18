<?php

namespace App\Domain\BookingRequests\Actions;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Shared\ValueObjects\Phone;
use App\Models\BookingRequest;
use App\Models\Workshop;

class SubmitPublicIntakeAction
{
    /**
     * @param  array{brand: ?string, model: ?string, year: ?int, license_plate: ?string}  $vehicle
     */
    public function handle(Workshop $workshop, string $message, string $phone, ?string $customerName, array $vehicle): BookingRequest
    {
        return BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => $customerName,
            'customer_phone' => $phone,
            'customer_phone_normalized' => (new Phone($phone))->normalize(),
            'problem_description' => trim($message),
            'original_message' => $message,
            'preferred_date' => null,
            'vehicle_brand' => $vehicle['brand'],
            'vehicle_model' => $vehicle['model'],
            'vehicle_year' => $vehicle['year'],
            'vehicle_license_plate' => $vehicle['license_plate'],
            'status' => BookingRequestStatus::New,
        ]);
    }
}
