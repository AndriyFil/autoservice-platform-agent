<?php

namespace App\Actions\Estimates;

use App\Enums\EstimateStatus;
use App\Models\RepairOrder;
use App\Models\WorkshopUser;

class GenerateRepairOrderEstimateAction
{
    public function __construct(
        private readonly PrepareEstimateForPdfAction $prepareEstimateForPdf,
        private readonly GenerateEstimatePdfAction $generateEstimatePdf,
    ) {}

    public function handle(WorkshopUser $activeWorkshopUser, RepairOrder $repairOrder): GenerateRepairOrderEstimateResult
    {
        $regenerated = $repairOrder->estimates()
            ->where('status', EstimateStatus::Generated)
            ->exists();

        $estimate = $this->prepareEstimateForPdf->handle($activeWorkshopUser, $repairOrder);
        $document = $this->generateEstimatePdf->handle($estimate);

        return new GenerateRepairOrderEstimateResult(
            document: $document,
            regenerated: $regenerated,
        );
    }
}
