<?php

namespace App\Actions\BookingRequests;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\WorkshopUser;
use Illuminate\Support\Facades\DB;

class CreateDashboardBookingRequestAction
{
    public function __construct(
        private readonly ResolveBookingRequestCustomerAction $resolveBookingRequestCustomer,
        private readonly CreateBookingRequestVehicleAction $createBookingRequestVehicle,
    ) {}

    /**
     * @param  array{
     *     customer_id?: int|null,
     *     customer_name: string,
     *     customer_phone: string,
     *     problem_description: string,
     *     preferred_date?: string|null,
     *     vehicle?: array{brand?: string|null, model?: string|null, license_plate?: string|null}
     * }  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, array $data): BookingRequest
    {
        return DB::transaction(function () use ($activeWorkshopUser, $data): BookingRequest {
            $workshop = $activeWorkshopUser->workshop;
            $customer = $this->resolveBookingRequestCustomer->handle(
                $workshop,
                $data['customer_name'],
                $data['customer_phone'],
                $data['customer_id'] ?? null,
            );
            $vehicle = $this->createBookingRequestVehicle->handle($workshop, $customer, $data['vehicle'] ?? []);

            return BookingRequest::create([
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle?->id,
                'created_by_user_id' => $activeWorkshopUser->user_id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'problem_description' => $data['problem_description'],
                'preferred_date' => $data['preferred_date'] ?? null,
                'status' => BookingRequestStatus::New,
            ]);
        });
    }
}
