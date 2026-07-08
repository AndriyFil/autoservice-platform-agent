<?php

namespace App\Queries\Dashboard;

use App\Enums\RepairOrderStatus;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;

class DashboardRepairOrdersQuery
{
    /**
     * @return array<int, array{
     *     id: int,
     *     customerName: string,
     *     problemDescription: string|null,
     *     status: array{value: string, label: string},
     *     availableStatusTransitions: array<int, array{value: string, label: string}>,
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
                'availableStatusTransitions' => $this->availableStatusTransitions($repairOrder->status),
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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableStatusTransitions(RepairOrderStatus $status): array
    {
        $actionLabels = [
            RepairOrderStatus::InProgress->value => 'Start work',
            RepairOrderStatus::Completed->value => 'Complete order',
            RepairOrderStatus::Cancelled->value => 'Cancel order',
        ];

        return collect([
            RepairOrderStatus::InProgress,
            RepairOrderStatus::Completed,
            RepairOrderStatus::Cancelled,
        ])
            ->filter(fn (RepairOrderStatus $targetStatus): bool => $status->canTransitionTo($targetStatus))
            ->map(fn (RepairOrderStatus $targetStatus): array => [
                'value' => $targetStatus->value,
                'label' => $actionLabels[$targetStatus->value],
            ])
            ->values()
            ->all();
    }
}
