<?php

namespace App\Actions\BookingRequests;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Workshop;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;

class CreatePublicBookingRequestAction
{
    public function __construct(
        private readonly ResolveBookingRequestCustomerAction $resolveBookingRequestCustomer,
        private readonly CreateBookingRequestVehicleAction $createBookingRequestVehicle,
    ) {}

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
        return DB::transaction(function () use ($workshop, $data): BookingRequest {
            $customerName = $data['customer_name'];
            $customerPhone = $data['customer_phone'];
            $customer = $this->resolveBookingRequestCustomer->handle($workshop, $customerName, $customerPhone);
            $vehicle = $this->createBookingRequestVehicle->handle($workshop, $customer, $data['vehicle'] ?? []);

            return BookingRequest::create([
                'workshop_id' => $workshop->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle?->id,
                'created_by_user_id' => null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'customer_phone_normalized' => (new Phone($customerPhone))->normalize(),
                'problem_description' => $data['problem_description'],
                'preferred_date' => $data['preferred_date'] ?? null,
                'status' => BookingRequestStatus::New,
            ]);
        });
    }
}
