<?php

namespace App\Queries\Dashboard;

use App\Models\RepairOrder;
use App\Models\WorkshopUser;

class DashboardRepairOrderDetailsQuery
{
    /**
     * @return array{
     *     id: int,
     *     status: array{value: string, label: string},
     *     problemDescription: string,
     *     openedAt: string,
     *     closedAt: string|null,
     *     customer: array{id: int, name: string, phone: string},
     *     vehicle: array{id: int, brand: string|null, model: string|null, licensePlate: string|null}|null,
     *     bookingRequest: array{id: int, status: array{value: string, label: string}, preferredDate: string|null, createdAt: string}|null
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): array
    {
        $repairOrder = RepairOrder::query()
            ->with(['customer', 'vehicle', 'bookingRequest'])
            ->whereKey($repairOrder->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return [
            'id' => $repairOrder->id,
            'status' => [
                'value' => $repairOrder->status->value,
                'label' => $repairOrder->status->label(),
            ],
            'problemDescription' => $repairOrder->problem_description,
            'openedAt' => $repairOrder->opened_at->toISOString(),
            'closedAt' => $repairOrder->closed_at?->toISOString(),
            'customer' => [
                'id' => $repairOrder->customer->id,
                'name' => $repairOrder->customer->name,
                'phone' => $repairOrder->customer->phone,
            ],
            'vehicle' => $repairOrder->vehicle
                ? [
                    'id' => $repairOrder->vehicle->id,
                    'brand' => $repairOrder->vehicle->brand,
                    'model' => $repairOrder->vehicle->model,
                    'licensePlate' => $repairOrder->vehicle->license_plate,
                ]
                : null,
            'bookingRequest' => $repairOrder->bookingRequest
                ? [
                    'id' => $repairOrder->bookingRequest->id,
                    'status' => [
                        'value' => $repairOrder->bookingRequest->status->value,
                        'label' => $repairOrder->bookingRequest->status->label(),
                    ],
                    'preferredDate' => $repairOrder->bookingRequest->preferred_date?->toDateString(),
                    'createdAt' => $repairOrder->bookingRequest->created_at->toISOString(),
                ]
                : null,
        ];
    }
}
