<?php

namespace App\Actions\Estimates;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class PrepareEstimateForPdfAction
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

            if (! $this->repairOrderAllowsEstimateGeneration($repairOrder->status)) {
                throw new DomainException(__('repair_orders.estimate_errors.repair_order_locked'));
            }

            if ($repairOrder->lines->isEmpty()) {
                throw new DomainException(__('repair_orders.estimate_errors.missing_lines'));
            }

            $estimate = Estimate::query()
                ->where('repair_order_id', $repairOrder->id)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            $nextVersion = $estimate instanceof Estimate ? $estimate->version + 1 : 1;

            $estimate = $this->createEstimate(
                activeWorkshopUser: $activeWorkshopUser,
                repairOrder: $repairOrder,
                version: $nextVersion,
            );

            if ($repairOrder->status === RepairOrderStatus::Draft) {
                $repairOrder->update([
                    'status' => RepairOrderStatus::Estimated,
                ]);
            }

            return $estimate->refresh()->load([
                'lines',
                'repairOrder.customer',
                'repairOrder.vehicle',
                'repairOrder.workshop',
            ]);
        });
    }

    private function repairOrderAllowsEstimateGeneration(RepairOrderStatus $status): bool
    {
        return in_array($status, [RepairOrderStatus::Draft, RepairOrderStatus::Estimated, RepairOrderStatus::InProgress], true);
    }

    private function createEstimate(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder, int $version): Estimate
    {
        $estimate = Estimate::create([
            'repair_order_id' => $repairOrder->id,
            'version' => $version,
            'status' => EstimateStatus::Generated,
            'subtotal_cents' => $repairOrder->subtotalCents(),
            'tax_cents' => $repairOrder->taxCents(),
            'total_cents' => $repairOrder->totalCents(),
            'currency' => config('app.currency', 'USD'),
            'requires_customer_approval' => $repairOrder->requires_estimate_approval,
            'created_by_user_id' => $activeWorkshopUser->user_id,
            'generated_at' => now(),
        ]);

        $this->createEstimateLines($estimate, $repairOrder);

        return $estimate;
    }

    private function createEstimateLines(Estimate $estimate, RepairOrder $repairOrder): void
    {
        $repairOrder->lines->each(function (RepairOrderLine $line) use ($estimate): void {
            $estimate->lines()->create($line->toEstimateLineAttributes());
        });
    }
}
