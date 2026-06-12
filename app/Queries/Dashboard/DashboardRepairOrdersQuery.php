<?php

namespace App\Queries\Dashboard;

use App\Models\RepairOrder;
use App\Models\WorkshopUser;

class DashboardRepairOrdersQuery
{
    /**
     * @return array<int, array{
     *     id: int,
     *     customerName: string,
     *     problemDescription: string,
     *     status: array{value: string, label: string},
     *     vehicle: array{brand: string|null, model: string|null, licensePlate: string|null}|null,
     *     openedAt: string,
     *     closedAt: string|null
     * }>
     */
    public function handle(WorkshopUser $activeWorkshopUser): array
    {
        return RepairOrder::query()
            ->with(['customer', 'vehicle'])
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->orderByDesc('opened_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RepairOrder $repairOrder): array => [
                'id' => $repairOrder->id,
                'customerName' => $repairOrder->customer->name,
                'problemDescription' => $repairOrder->problem_description,
                'status' => [
                    'value' => $repairOrder->status->value,
                    'label' => $repairOrder->status->label(),
                ],
                'vehicle' => $repairOrder->vehicle
                    ? [
                        'brand' => $repairOrder->vehicle->brand,
                        'model' => $repairOrder->vehicle->model,
                        'licensePlate' => $repairOrder->vehicle->license_plate,
                    ]
                    : null,
                'openedAt' => $repairOrder->opened_at->toISOString(),
                'closedAt' => $repairOrder->closed_at?->toISOString(),
            ])
            ->all();
    }
}
