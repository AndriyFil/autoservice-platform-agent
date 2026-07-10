<?php

namespace App\Domain\RepairOrders\Services;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;

class RepairOrderStatusTransitionService
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function availableManualTransitions(RepairOrderStatus $status): array
    {
        return collect($status->manualTransitions())
            ->map(fn (RepairOrderStatus $targetStatus): array => [
                'value' => $targetStatus->value,
                'label' => $this->labelFor($targetStatus),
            ])
            ->values()
            ->all();
    }

    public function labelFor(RepairOrderStatus $status): string
    {
        return __("repair_orders.status_actions.{$status->value}");
    }
}
