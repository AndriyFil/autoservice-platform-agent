<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\MarkRepairOrderEstimatedAction;
use App\Http\Requests\MarkRepairOrderEstimatedRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class EstimateDashboardRepairOrderController extends Controller
{
    public function store(
        MarkRepairOrderEstimatedRequest $request,
        RepairOrder $repairOrder,
        MarkRepairOrderEstimatedAction $markRepairOrderEstimated,
    ): RedirectResponse {
        try {
            $markRepairOrderEstimated->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order marked as estimated.');
    }
}
