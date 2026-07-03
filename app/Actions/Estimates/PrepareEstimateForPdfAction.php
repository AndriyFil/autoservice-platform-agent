<?php

namespace App\Actions\Estimates;

use App\Enums\DocumentStatus;
use App\Enums\EstimateStatus;
use App\Enums\RepairOrderStatus;
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
                throw new DomainException(__('repair_orders.regenerate_errors.repair_order_locked'));
            }

            if ($repairOrder->lines->isEmpty()) {
                throw new DomainException(__('repair_orders.estimate_errors.missing_lines'));
            }

            $estimate = Estimate::query()
                ->where('repair_order_id', $repairOrder->id)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            if ($estimate === null) {
                $estimate = $this->createEstimate($activeWorkshopUser, $repairOrder);
            } elseif ($estimate->status === EstimateStatus::Generated) {
                $this->rebuildEstimateSnapshot($estimate, $repairOrder);
                $this->archiveGeneratedDocuments($estimate);
            } else {
                throw new DomainException(__('repair_orders.regenerate_errors.estimate_locked'));
            }

            $estimate->update([
                'status' => EstimateStatus::Generated,
                'generated_at' => now(),
            ]);

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
        return in_array($status, [RepairOrderStatus::Draft, RepairOrderStatus::Estimated], true);
    }

    private function createEstimate(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): Estimate
    {
        $estimate = Estimate::create([
            'repair_order_id' => $repairOrder->id,
            'version' => 1,
            'status' => EstimateStatus::Draft,
            'subtotal_cents' => $repairOrder->subtotalCents(),
            'tax_cents' => $repairOrder->taxCents(),
            'total_cents' => $repairOrder->totalCents(),
            'currency' => config('app.currency', 'USD'),
            'created_by_user_id' => $activeWorkshopUser->user_id,
        ]);

        $this->createEstimateLines($estimate, $repairOrder);

        return $estimate;
    }

    private function rebuildEstimateSnapshot(Estimate $estimate, RepairOrder $repairOrder): void
    {
        $estimate->lines()->delete();
        $this->createEstimateLines($estimate, $repairOrder);

        $estimate->update([
            'subtotal_cents' => $repairOrder->subtotalCents(),
            'tax_cents' => $repairOrder->taxCents(),
            'total_cents' => $repairOrder->totalCents(),
        ]);
    }

    private function createEstimateLines(Estimate $estimate, RepairOrder $repairOrder): void
    {
        $repairOrder->lines->each(function (RepairOrderLine $line) use ($estimate): void {
            $estimate->lines()->create($line->toEstimateLineAttributes());
        });
    }

    private function archiveGeneratedDocuments(Estimate $estimate): void
    {
        $estimate->generatedEstimatePdfDocuments()->update([
            'status' => DocumentStatus::Archived,
        ]);
    }
}
