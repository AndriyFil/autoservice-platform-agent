<?php

namespace App\Actions\Estimates;

use App\Enums\EstimateStatus;
use App\Enums\RepairOrderStatus;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateEstimateFromRepairOrderAction
{
    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): Estimate
    {
        return DB::transaction(function () use ($activeWorkshopUser, $repairOrder): Estimate {
            $repairOrder = RepairOrder::query()
                ->with('lines')
                ->whereKey($repairOrder->id)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($repairOrder->lines->isEmpty()) {
                throw new DomainException(__('repair_orders.estimate_errors.missing_lines'));
            }

            $nextVersion = ((int) Estimate::query()
                ->where('repair_order_id', $repairOrder->id)
                ->max('version')) + 1;

            $estimate = Estimate::create([
                'repair_order_id' => $repairOrder->id,
                'version' => $nextVersion,
                'status' => EstimateStatus::Draft,
                'subtotal_cents' => $repairOrder->subtotalCents(),
                'tax_cents' => $repairOrder->taxCents(),
                'total_cents' => $repairOrder->totalCents(),
                'currency' => config('app.currency', 'USD'),
                'created_by_user_id' => $activeWorkshopUser->user_id,
            ]);

            $repairOrder->lines->each(function (RepairOrderLine $line) use ($estimate): void {
                $estimate->lines()->create([
                    'type' => $line->type,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price_cents' => $line->unit_price_cents,
                    'tax_rate' => $line->tax_rate,
                    'subtotal_cents' => $line->subtotalCents(),
                    'tax_cents' => $line->taxCents(),
                    'total_cents' => $line->totalCents(),
                    'sort_order' => $line->sort_order,
                ]);
            });

            if ($repairOrder->status === RepairOrderStatus::Draft) {
                $repairOrder->update([
                    'status' => RepairOrderStatus::Estimated,
                ]);
            }

            return $estimate->refresh()->load('lines', 'repairOrder');
        });
    }
}
