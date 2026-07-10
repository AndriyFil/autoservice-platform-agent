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
        return collect([
            RepairOrderStatus::InProgress,
            RepairOrderStatus::Completed,
            RepairOrderStatus::Cancelled,
        ])
            ->filter(fn (RepairOrderStatus $targetStatus): bool => $status->canTransitionTo($targetStatus))
            ->map(fn (RepairOrderStatus $targetStatus): array => [
                'value' => $targetStatus->value,
                'label' => $this->labelFor($targetStatus),
            ])
            ->values()
            ->all();
    }

    public function labelFor(RepairOrderStatus $status): string
    {
        return match ($status) {
            RepairOrderStatus::Estimated => 'Mark as estimated',
            RepairOrderStatus::InProgress => 'Start work',
            RepairOrderStatus::Completed => 'Complete order',
            RepairOrderStatus::Cancelled => 'Cancel order',
            RepairOrderStatus::Draft => 'Move to draft',
        };
    }
}
