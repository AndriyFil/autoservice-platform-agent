<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\AddRepairOrderLineAction;
use App\Actions\RepairOrders\DeleteRepairOrderLineAction;
use App\Actions\RepairOrders\UpdateRepairOrderLineAction;
use App\Http\Requests\StoreRepairOrderLineRequest;
use App\Http\Requests\UpdateRepairOrderLineRequest;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRepairOrderLineController extends Controller
{
    public function store(
        StoreRepairOrderLineRequest $request,
        RepairOrder $repairOrder,
        AddRepairOrderLineAction $addRepairOrderLine,
    ): RedirectResponse {
        $addRepairOrderLine->handle(
            $request->attributes->get('activeWorkshopUser'),
            $repairOrder,
            $request->validated(),
        );

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order line added.');
    }

    public function update(
        UpdateRepairOrderLineRequest $request,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
        UpdateRepairOrderLineAction $updateRepairOrderLine,
    ): RedirectResponse {
        $updateRepairOrderLine->handle(
            $request->attributes->get('activeWorkshopUser'),
            $repairOrder,
            $repairOrderLine,
            $request->validated(),
        );

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order line updated.');
    }

    public function destroy(
        Request $request,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
        DeleteRepairOrderLineAction $deleteRepairOrderLine,
    ): RedirectResponse {
        $deleteRepairOrderLine->handle(
            $request->attributes->get('activeWorkshopUser'),
            $repairOrder,
            $repairOrderLine,
        );

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order line deleted.');
    }
}
