<?php

namespace App\Domain\Customers\Actions;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkshopUser;

class UpdateCustomerVehicleAction
{
    /**
     * @param  array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, Customer $customer, Vehicle $vehicle, array $data): Vehicle
    {
        $customer = Customer::query()
            ->whereKey($customer->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        $vehicle = Vehicle::query()
            ->whereKey($vehicle->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $vehicle->update([
            'brand' => $this->nullableTrim($data['make'] ?? null),
            'model' => $this->nullableTrim($data['model'] ?? null),
            'year' => $data['year'] ?? null,
            'license_plate' => $this->nullableTrim($data['plate'] ?? null),
        ]);

        return $vehicle;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
