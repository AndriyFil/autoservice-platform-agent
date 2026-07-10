<?php

namespace App\Domain\RepairOrders\Actions;

use App\Domain\RepairOrders\Exceptions\FinalRepairOrderCannotBeChanged;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;
use Illuminate\Support\Facades\DB;

class RemoveRepairOrderLineAction
{
    public function handle(
        WorkshopUser $activeWorkshopUser,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
    ): void {
        DB::transaction(function () use ($activeWorkshopUser, $repairOrder, $repairOrderLine): void {
            $repairOrder = RepairOrder::query()
                ->whereKey($repairOrder->id)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($repairOrder->status->isFinal()) {
                throw FinalRepairOrderCannotBeChanged::lines();
            }

            $repairOrderLine = RepairOrderLine::query()
                ->whereKey($repairOrderLine->id)
                ->where('repair_order_id', $repairOrder->id)
                ->firstOrFail();

            $repairOrderLine->delete();
        });
    }
}
