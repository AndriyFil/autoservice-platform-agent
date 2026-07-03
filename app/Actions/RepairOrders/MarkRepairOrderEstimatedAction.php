<?php

namespace App\Actions\RepairOrders;

use App\Enums\RepairOrderStatus;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;
use DomainException;

class MarkRepairOrderEstimatedAction
{
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): RepairOrder
    {
        $repairOrder = RepairOrder::query()
            ->withCount('lines')
            ->whereKey($repairOrder->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        if (! $repairOrder->status->canTransitionTo(RepairOrderStatus::Estimated)) {
            throw new DomainException('Only draft repair orders can be marked as estimated.');
        }

        if ($repairOrder->lines_count < 1) {
            throw new DomainException('Add at least one estimate line before marking this repair order as estimated.');
        }

        $repairOrder->status = RepairOrderStatus::Estimated;
        $repairOrder->save();

        return $repairOrder->refresh();
    }
}
