<?php

namespace App\Http\Controllers;

use App\Actions\RepairOrders\CreateRepairOrderAction;
use App\Http\Requests\StoreRepairOrderRequest;
use App\Models\RepairOrder;
use App\Queries\Dashboard\DashboardRepairOrderDetailsQuery;
use App\Queries\Dashboard\DashboardRepairOrderFormQuery;
use App\Queries\Dashboard\DashboardRepairOrdersQuery;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardRepairOrderController extends Controller
{
    public function index(
        Request $request,
        DashboardRepairOrdersQuery $repairOrdersQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard/RepairOrders/Index', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'repairOrders' => $repairOrdersQuery->handle($activeWorkshopUser),
        ]);
    }

    public function create(
        Request $request,
        DashboardRepairOrderFormQuery $repairOrderFormQuery,
    ): Response|RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;
        $formData = $repairOrderFormQuery->handle(
            $activeWorkshopUser,
            $request->integer('booking_request') ?: null,
        );

        if ($formData['existingRepairOrderId']) {
            return to_route('dashboard.repair-orders.show', $formData['existingRepairOrderId']);
        }

        return Inertia::render('Dashboard/RepairOrders/Create', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'customers' => $formData['customers'],
            'defaults' => $formData['defaults'],
            'sourceBookingRequest' => $formData['sourceBookingRequest'],
        ]);
    }

    public function store(
        StoreRepairOrderRequest $request,
        CreateRepairOrderAction $createRepairOrder,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        try {
            $repairOrder = $createRepairOrder->handle($activeWorkshopUser, $request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors([
                'repair_order' => $exception->getMessage(),
            ])->withInput();
        }

        return to_route('dashboard.repair-orders.show', $repairOrder)
            ->with('status', 'Repair order opened.');
    }

    public function show(
        Request $request,
        RepairOrder $repairOrder,
        DashboardRepairOrderDetailsQuery $repairOrderDetailsQuery,
    ): Response {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $activeWorkshop = $activeWorkshopUser->workshop;

        return Inertia::render('Dashboard/RepairOrders/Show', [
            'activeWorkshop' => [
                'id' => $activeWorkshop->id,
                'name' => $activeWorkshop->name,
                'slug' => $activeWorkshop->slug,
            ],
            'repairOrder' => $repairOrderDetailsQuery->handle($activeWorkshopUser, $repairOrder),
        ]);
    }
}
