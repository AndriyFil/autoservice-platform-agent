<?php

namespace App\Domain\RepairOrders\Actions;

use App\Domain\RepairOrders\Exceptions\FinalRepairOrderCannotBeChanged;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;
use Illuminate\Support\Facades\DB;

class AddRepairOrderLineAction
{
    /**
     * @param  array{type: string, description: string, quantity: numeric-string, unit_price_cents: int, tax_rate: numeric-string, sort_order?: int|null}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder, array $data): RepairOrderLine
    {
        return DB::transaction(function () use ($activeWorkshopUser, $repairOrder, $data): RepairOrderLine {
            $repairOrder = RepairOrder::query()
                ->whereKey($repairOrder->id)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($repairOrder->status->isFinal()) {
                throw FinalRepairOrderCannotBeChanged::lines();
            }

            return RepairOrderLine::create([
                'repair_order_id' => $repairOrder->id,
                'type' => $data['type'],
                'description' => $data['description'],
                'quantity' => $data['quantity'],
                'unit_price_cents' => $data['unit_price_cents'],
                'tax_rate' => $data['tax_rate'],
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }
}
