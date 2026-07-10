<?php

namespace App\Domain\RepairOrders\Actions;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\Vehicle;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateRepairOrderAction
{
    /**
     * @param  array{
     *     customer_id: int,
     *     vehicle_id?: int|null,
     *     problem_description: string,
     *     requires_estimate_approval?: bool,
     *     notes?: string|null,
     *     new_vehicle?: array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}
     * }  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, array $data): RepairOrder
    {
        return DB::transaction(function () use ($activeWorkshopUser, $data): RepairOrder {
            $customer = $this->resolveSelectedCustomer($activeWorkshopUser, $data['customer_id']);
            $vehicleId = $this->resolveVehicleId($activeWorkshopUser, $customer, $data);

            return RepairOrder::create([
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleId,
                'booking_request_id' => null,
                'status' => RepairOrderStatus::Draft,
                'requires_estimate_approval' => $data['requires_estimate_approval'] ?? true,
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $activeWorkshopUser->user_id,
                'problem_description' => $data['problem_description'],
                'opened_at' => now(),
                'closed_at' => null,
            ]);
        });
    }

    private function resolveSelectedCustomer(WorkshopUser $activeWorkshopUser, int $customerId): Customer
    {
        return Customer::query()
            ->whereKey($customerId)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();
    }

    /**
     * @param  array{
     *     vehicle_id?: int|null,
     *     new_vehicle?: array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}
     * }  $data
     */
    private function resolveVehicleId(WorkshopUser $activeWorkshopUser, Customer $customer, array $data): ?int
    {
        $newVehicle = $data['new_vehicle'] ?? [];

        if ($this->hasNewVehicleData($newVehicle)) {
            return Vehicle::create([
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'customer_id' => $customer->id,
                'brand' => $this->nullableTrim($newVehicle['make'] ?? null),
                'model' => $this->nullableTrim($newVehicle['model'] ?? null),
                'year' => $newVehicle['year'] ?? null,
                'license_plate' => $this->nullableTrim($newVehicle['plate'] ?? null),
            ])->id;
        }

        $vehicleId = $data['vehicle_id'] ?? null;

        if ($vehicleId === null) {
            return null;
        }

        $vehicle = Vehicle::query()
            ->whereKey($vehicleId)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->where('customer_id', $customer->id)
            ->first();

        if (! $vehicle) {
            throw new DomainException('The selected vehicle does not belong to this customer.');
        }

        return $vehicle->id;
    }

    /**
     * @param  array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}  $newVehicle
     */
    private function hasNewVehicleData(array $newVehicle): bool
    {
        return $this->nullableTrim($newVehicle['make'] ?? null) !== null
            || $this->nullableTrim($newVehicle['model'] ?? null) !== null
            || ($newVehicle['year'] ?? null) !== null
            || $this->nullableTrim($newVehicle['plate'] ?? null) !== null;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
