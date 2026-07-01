<?php

namespace App\Actions\RepairOrders;

use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;

class UpdateRepairOrderLineAction
{
    /**
     * @param  array{type: string, description: string, quantity: numeric-string, unit_price_cents: int, tax_rate: numeric-string, sort_order?: int|null}  $data
     */
    public function handle(
        WorkshopUser $activeWorkshopUser,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
        array $data,
    ): RepairOrderLine {
        $repairOrderLine = RepairOrderLine::query()
            ->whereKey($repairOrderLine->id)
            ->where('repair_order_id', $repairOrder->id)
            ->whereHas('repairOrder', fn ($query) => $query
                ->where('workshop_id', $activeWorkshopUser->workshop_id))
            ->firstOrFail();

        $repairOrderLine->update([
            'type' => $data['type'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price_cents' => $data['unit_price_cents'],
            'tax_rate' => $data['tax_rate'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return $repairOrderLine->refresh();
    }
}
