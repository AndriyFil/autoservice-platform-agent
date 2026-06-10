<?php

namespace App\Actions\BookingRequests;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($workshop, $data): BookingRequest {
            $customerName = $data['customer_name'];
            $customerPhone = $data['customer_phone'];
            $normalizedPhone = $this->normalizePhone($customerPhone);

            $customer = Customer::firstOrCreate(
                [
                    'workshop_id' => $workshop->id,
                    'normalized_phone' => $normalizedPhone,
                ],
                [
                    'name' => $customerName,
                    'phone' => $customerPhone,
                ],
            );

            $vehicle = $this->createVehicleIfProvided($workshop, $customer, $data['vehicle'] ?? []);

            return BookingRequest::create([
                'workshop_id' => $workshop->id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle?->id,
                'created_by_user_id' => null,
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'problem_description' => $data['problem_description'],
                'preferred_date' => $data['preferred_date'] ?? null,
                'status' => BookingRequestStatus::New,
            ]);
        });
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * @param  array{brand?: string|null, model?: string|null, license_plate?: string|null}  $data
     */
    private function createVehicleIfProvided(Workshop $workshop, Customer $customer, array $data): ?Vehicle
    {
        $vehicleData = [
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'license_plate' => $data['license_plate'] ?? null,
        ];

        if (! $this->hasVehicleData($vehicleData)) {
            return null;
        }

        return Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            ...$vehicleData,
        ]);
    }

    /**
     * @param  array{brand?: string|null, model?: string|null, license_plate?: string|null}  $vehicleData
     */
    private function hasVehicleData(array $vehicleData): bool
    {
        return ($vehicleData['brand'] ?? null) !== null
            || ($vehicleData['model'] ?? null) !== null
            || ($vehicleData['license_plate'] ?? null) !== null;
    }
}
