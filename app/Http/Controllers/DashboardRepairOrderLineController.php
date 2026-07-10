<?php

namespace App\Http\Controllers;

use App\Domain\RepairOrders\Actions\AddRepairOrderLineAction;
use App\Domain\RepairOrders\Actions\RemoveRepairOrderLineAction;
use App\Domain\RepairOrders\Actions\UpdateRepairOrderLineAction;
use App\Http\Requests\StoreRepairOrderLineRequest;
use App\Http\Requests\UpdateRepairOrderLineRequest;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRepairOrderLineController extends Controller
{
    public function store(
        StoreRepairOrderLineRequest $request,
        RepairOrder $repairOrder,
        AddRepairOrderLineAction $addRepairOrderLine,
    ): RedirectResponse {
        try {
            $addRepairOrderLine->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
                $request->validated(),
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'repair_order_line' => $exception->getMessage(),
            ])->withInput();
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order line added.');
    }

    public function update(
        UpdateRepairOrderLineRequest $request,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
        UpdateRepairOrderLineAction $updateRepairOrderLine,
    ): RedirectResponse {
        try {
            $updateRepairOrderLine->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
                $repairOrderLine,
                $request->validated(),
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'repair_order_line' => $exception->getMessage(),
            ])->withInput();
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order line updated.');
    }

    public function destroy(
        Request $request,
        RepairOrder $repairOrder,
        RepairOrderLine $repairOrderLine,
        RemoveRepairOrderLineAction $deleteRepairOrderLine,
    ): RedirectResponse {
        try {
            $deleteRepairOrderLine->handle(
                $request->attributes->get('activeWorkshopUser'),
                $repairOrder,
                $repairOrderLine,
            );
        } catch (DomainException $exception) {
            return back()->withErrors([
                'repair_order_line' => $exception->getMessage(),
            ]);
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order line deleted.');
    }
}
