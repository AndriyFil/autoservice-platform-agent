<?php

namespace App\Actions\RepairOrders;

use App\Enums\RepairOrderStatus;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;
use DomainException;

class UpdateRepairOrderEstimateApprovalRequirementAction
{
    public function handle(
        WorkshopUser $activeWorkshopUser,
        RepairOrder $repairOrder,
        bool $requiresEstimateApproval,
    ): RepairOrder {
        $repairOrder = RepairOrder::query()
            ->whereKey($repairOrder->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        if (in_array($repairOrder->status, [RepairOrderStatus::Completed, RepairOrderStatus::Cancelled], true)) {
            throw new DomainException('Estimate approval requirement cannot be changed after the repair order is closed.');
        }

        $repairOrder->requires_estimate_approval = $requiresEstimateApproval;
        $repairOrder->save();

        return $repairOrder->refresh();
    }
}
