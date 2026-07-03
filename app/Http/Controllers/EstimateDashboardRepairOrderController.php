<?php

namespace App\Http\Controllers;

use App\Actions\Estimates\GenerateRepairOrderEstimateAction;
use App\Http\Requests\MarkRepairOrderEstimatedRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class EstimateDashboardRepairOrderController extends Controller
{
    public function store(
        MarkRepairOrderEstimatedRequest $request,
        RepairOrder $repairOrder,
        GenerateRepairOrderEstimateAction $generateRepairOrderEstimate,
    ): RedirectResponse {
        try {
            $result = $generateRepairOrderEstimate->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', $result->regenerated
                ? __('repair_orders.estimate_regenerated')
                : __('repair_orders.estimate_created'));
    }
}
