<?php

namespace App\Http\Controllers;

use App\Domain\BookingRequests\Actions\CreateRepairOrderFromBookingRequestAction;
use App\Domain\RepairOrders\Actions\ChangeRepairOrderStatusAction;
use App\Domain\RepairOrders\Actions\CreateRepairOrderAction;
use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Domain\RepairOrders\Queries\RepairOrderIndexQuery;
use App\Domain\RepairOrders\Queries\RepairOrderShowQuery;
use App\Http\Requests\StoreRepairOrderRequest;
use App\Http\Requests\UpdateRepairOrderStatusRequest;
use App\Models\RepairOrder;
use App\Queries\Dashboard\DashboardRepairOrderFormQuery;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardRepairOrderController extends Controller
{
    public function index(
        Request $request,
        RepairOrderIndexQuery $repairOrdersQuery,
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
        CreateRepairOrderFromBookingRequestAction $createRepairOrderFromBookingRequest,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');
        $data = $request->validated();

        try {
            $repairOrder = isset($data['booking_request_id'])
                ? $createRepairOrderFromBookingRequest->handle($activeWorkshopUser, $data)
                : $createRepairOrder->handle($activeWorkshopUser, $data);
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
        RepairOrderShowQuery $repairOrderDetailsQuery,
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

    public function updateStatus(
        UpdateRepairOrderStatusRequest $request,
        RepairOrder $repairOrder,
        ChangeRepairOrderStatusAction $changeRepairOrderStatus,
    ): RedirectResponse {
        $activeWorkshopUser = $request->attributes->get('activeWorkshopUser');

        try {
            $changeRepairOrderStatus->handle($activeWorkshopUser, $repairOrder, $request->status());
        } catch (DomainException $exception) {
            return back()->withErrors([
                'status' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', $this->statusChangeMessage($request->status()));
    }

    private function statusChangeMessage(RepairOrderStatus $status): string
    {
        return match ($status) {
            RepairOrderStatus::InProgress => 'Repair order started.',
            RepairOrderStatus::Completed => 'Repair order completed.',
            RepairOrderStatus::Cancelled => 'Repair order cancelled.',
            default => 'Repair order status updated.',
        };
    }
}
