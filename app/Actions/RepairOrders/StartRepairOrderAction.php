<?php

namespace App\Actions\RepairOrders;

use App\Enums\RepairOrderStatus;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;
use DomainException;

class StartRepairOrderAction
{
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): RepairOrder
    {
        $repairOrder = RepairOrder::query()
            ->whereKey($repairOrder->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        if (! $repairOrder->status->canTransitionTo(RepairOrderStatus::InProgress)) {
            throw new DomainException('This repair order cannot be started.');
        }

        $repairOrder->status = RepairOrderStatus::InProgress;
        $repairOrder->save();

        return $repairOrder->refresh();
    }
}
