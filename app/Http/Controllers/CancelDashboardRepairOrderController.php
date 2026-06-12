<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\CancelRepairOrderAction;
use App\Http\Requests\CancelRepairOrderRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class CancelDashboardRepairOrderController extends Controller
{
    public function store(
        CancelRepairOrderRequest $request,
        RepairOrder $repairOrder,
        CancelRepairOrderAction $cancelRepairOrder,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        try {
            $cancelRepairOrder->handle($activeWorkshopUser, $repairOrder);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Repair order cancelled.');
    }
}
