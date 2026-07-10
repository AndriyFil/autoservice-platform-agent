<?php

namespace App\Domain\Estimates\Actions;

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
        $estimate = $this->prepareEstimateForPdf->handle($activeWorkshopUser, $repairOrder);
        $document = $this->generateEstimatePdf->handle($estimate);

        return new GenerateRepairOrderEstimateResult(
            document: $document,
        );
    }
}
