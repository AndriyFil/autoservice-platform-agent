<?php

namespace App\Actions\RepairOrders;

use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;

class AddRepairOrderLineAction
{
    /**
     * @param  array{type: string, description: string, quantity: numeric-string, unit_price_cents: int, tax_rate: numeric-string, sort_order?: int|null}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder, array $data): RepairOrderLine
    {
        $repairOrder = RepairOrder::query()
            ->whereKey($repairOrder->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return RepairOrderLine::create([
            'repair_order_id' => $repairOrder->id,
            'type' => $data['type'],
            'description' => $data['description'],
            'quantity' => $data['quantity'],
            'unit_price_cents' => $data['unit_price_cents'],
            'tax_rate' => $data['tax_rate'],
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }
}
