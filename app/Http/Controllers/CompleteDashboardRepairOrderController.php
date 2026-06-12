<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\CompleteRepairOrderAction;
use App\Http\Requests\CompleteRepairOrderRequest;
use App\Models\RepairOrder;
use DomainException;
use Illuminate\Http\RedirectResponse;

class CompleteDashboardRepairOrderController extends Controller
{
    public function store(
        CompleteRepairOrderRequest $request,
        RepairOrder $repairOrder,
        CompleteRepairOrderAction $completeRepairOrder,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        try {
            $completeRepairOrder->handle($activeWorkshopUser, $repairOrder);
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'Repair order completed.');
    }
}
