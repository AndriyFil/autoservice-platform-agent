<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\UpdateRepairOrderEstimateApprovalRequirementAction;
use App\Http\Requests\UpdateRepairOrderEstimateApprovalRequirementRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class DashboardRepairOrderEstimateApprovalRequirementController extends Controller
{
    public function update(
        UpdateRepairOrderEstimateApprovalRequirementRequest $request,
        RepairOrder $repairOrder,
        UpdateRepairOrderEstimateApprovalRequirementAction $updateEstimateApprovalRequirement,
    ): RedirectResponse {
        try {
            $updateEstimateApprovalRequirement->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
                $request->boolean('requires_estimate_approval'),
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'requires_estimate_approval' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Estimate approval requirement updated.');
    }
}
