<?php

namespace App\Domain\RepairOrders\Actions;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class ChangeRepairOrderStatusAction
{
    public function handle(
        WorkshopUser $activeWorkshopUser,
        RepairOrder $repairOrder,
        RepairOrderStatus $targetStatus,
    ): RepairOrder {
        return DB::transaction(function () use ($activeWorkshopUser, $repairOrder, $targetStatus): RepairOrder {
            $repairOrder = RepairOrder::query()
                ->whereKey($repairOrder->id)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $repairOrder->status->canTransitionTo($targetStatus)) {
                throw new DomainException('This repair order cannot move to the selected status.');
            }

            if ($targetStatus === RepairOrderStatus::Estimated) {
                throw new DomainException('Generate an estimate PDF to mark this repair order as estimated.');
            }

            $repairOrder->status = $targetStatus;

            if ($targetStatus->isFinal()) {
                $repairOrder->closed_at = now();
            }

            $repairOrder->save();

            return $repairOrder->refresh();
        });
    }
}
