<?php

namespace App\Actions\RepairOrders;

use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;

class DeleteRepairOrderLineAction
{
    public function handle(
        WorkshopUser $activeWorkshopUser,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
    ): void {
        $repairOrderLine = RepairOrderLine::query()
            ->whereKey($repairOrderLine->id)
            ->where('repair_order_id', $repairOrder->id)
            ->whereHas('repairOrder', fn ($query) => $query
                ->where('workshop_id', $activeWorkshopUser->workshop_id))
            ->firstOrFail();

        $repairOrderLine->delete();
    }
}
