<?php

namespace App\Http\Controllers;

use App\Actions\Estimates\CreateEstimateFromRepairOrderAction;
use App\Actions\Estimates\GenerateEstimatePdfAction;
use App\Http\Requests\MarkRepairOrderEstimatedRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class EstimateDashboardRepairOrderController extends Controller
{
    public function store(
        MarkRepairOrderEstimatedRequest $request,
        RepairOrder $repairOrder,
        CreateEstimateFromRepairOrderAction $createEstimateFromRepairOrder,
        GenerateEstimatePdfAction $generateEstimatePdf,
    ): RedirectResponse {
        try {
            $estimate = $createEstimateFromRepairOrder->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
            );
            $generateEstimatePdf->handle($estimate);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', __('repair_orders.estimate_created'));
    }
}
