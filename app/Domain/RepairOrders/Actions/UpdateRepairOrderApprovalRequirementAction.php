<?php

namespace App\Domain\RepairOrders\Actions;

use App\Domain\RepairOrders\Exceptions\FinalRepairOrderCannotBeChanged;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;
use Illuminate\Support\Facades\DB;

class UpdateRepairOrderApprovalRequirementAction
{
    public function handle(
        WorkshopUser $activeWorkshopUser,
        RepairOrder $repairOrder,
        bool $requiresEstimateApproval,
    ): RepairOrder {
        return DB::transaction(function () use ($activeWorkshopUser, $repairOrder, $requiresEstimateApproval): RepairOrder {
            $repairOrder = RepairOrder::query()
                ->whereKey($repairOrder->id)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($repairOrder->status->isFinal()) {
                throw FinalRepairOrderCannotBeChanged::approvalRequirement();
            }

            $repairOrder->requires_estimate_approval = $requiresEstimateApproval;
            $repairOrder->save();

            return $repairOrder->refresh();
        });
    }
}
