<?php

namespace App\Http\Controllers;

use App\Domain\Estimates\Actions\GenerateRepairOrderEstimateAction;
use App\Http\Requests\GenerateRepairOrderEstimateRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class EstimateDashboardRepairOrderController extends Controller
{
    public function store(
        GenerateRepairOrderEstimateRequest $request,
        RepairOrder $repairOrder,
        GenerateRepairOrderEstimateAction $generateRepairOrderEstimate,
    ): RedirectResponse {
        try {
            $generateRepairOrderEstimate->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', __('repair_orders.estimate_created'));
    }
}
