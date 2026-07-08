<?php

namespace App\Actions\Customers;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkshopUser;

class CreateCustomerVehicleAction
{
    /**
     * @param  array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, Customer $customer, array $data): Vehicle
    {
        $customer = Customer::query()
            ->whereKey($customer->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return Vehicle::create([
            'workshop_id' => $activeWorkshopUser->workshop_id,
            'customer_id' => $customer->id,
            'brand' => $this->nullableTrim($data['make'] ?? null),
            'model' => $this->nullableTrim($data['model'] ?? null),
            'year' => $data['year'] ?? null,
            'license_plate' => $this->nullableTrim($data['plate'] ?? null),
        ]);
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
