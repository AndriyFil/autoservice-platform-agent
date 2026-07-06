<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\StartRepairOrderAction;
use App\Http\Requests\StartRepairOrderRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class StartDashboardRepairOrderController extends Controller
{
    public function store(
        StartRepairOrderRequest $request,
        RepairOrder $repairOrder,
        StartRepairOrderAction $startRepairOrder,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        try {
            $startRepairOrder->handle($activeWorkshopUser, $repairOrder);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Repair order started.');
    }
}
