<?php

namespace App\Actions\BookingRequests;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Workshop;

class CreateBookingRequestVehicleAction
{
    /**
     * @param  array{brand?: string|null, model?: string|null, license_plate?: string|null}  $data
     */
    public function handle(Workshop $workshop, Customer $customer, array $data): ?Vehicle
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
