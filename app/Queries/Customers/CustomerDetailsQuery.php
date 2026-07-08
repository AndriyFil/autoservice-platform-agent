<?php

namespace App\Queries\Customers;

use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\Vehicle;
use App\Models\WorkshopUser;

class CustomerDetailsQuery
{
    /**
     * @return array{
     *     id: int,
     *     name: string|null,
     *     phone: string,
     *     createdAt: string,
     *     vehicles: array<int, array{id: int, brand: string|null, model: string|null, year: int|null, licensePlate: string|null}>,
     *     bookingRequests: array<int, array{
     *         id: int,
     *         status: array{value: string, label: string},
     *         problemDescription: string,
     *         preferredDate: string|null,
     *         createdAt: string,
     *         showUrl: string
     *     }>,
     *     repairOrders: array<int, array{
     *         id: int,
     *         status: array{value: string, label: string},
     *         problemDescription: string|null,
     *         vehicle: array{id: int, brand: string|null, model: string|null, year: int|null, licensePlate: string|null}|null,
     *         openedAt: string,
     *         createdAt: string,
     *         showUrl: string
     *     }>
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser, Customer $customer): array
    {
        $customer = Customer::query()
            ->with([
                'vehicles' => fn ($query) => $query
                    ->orderBy('brand')
                    ->orderBy('model')
                    ->orderBy('id'),
                'bookingRequests' => fn ($query) => $query
                    ->orderByDesc('created_at')
                    ->orderByDesc('id'),
                'repairOrders' => fn ($query) => $query
                    ->with('vehicle')
                    ->orderByDesc('opened_at')
                    ->orderByDesc('id'),
            ])
            ->whereKey($customer->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'createdAt' => $customer->created_at->toISOString(),
            'vehicles' => $customer->vehicles
                ->map(fn (Vehicle $vehicle): array => [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'licensePlate' => $vehicle->license_plate,
                ])
                ->all(),
            'bookingRequests' => $customer->bookingRequests
                ->map(fn (BookingRequest $bookingRequest): array => [
                    'id' => $bookingRequest->id,
                    'status' => [
                        'value' => $bookingRequest->status->value,
                        'label' => $bookingRequest->status->label(),
                    ],
                    'problemDescription' => $bookingRequest->problem_description,
                    'preferredDate' => $bookingRequest->preferred_date?->toDateString(),
                    'createdAt' => $bookingRequest->created_at->toISOString(),
                    'showUrl' => route('dashboard.booking-requests.show', $bookingRequest),
                ])
                ->all(),
            'repairOrders' => $customer->repairOrders
                ->map(fn (RepairOrder $repairOrder): array => [
                    'id' => $repairOrder->id,
                    'status' => [
                        'value' => $repairOrder->status->value,
                        'label' => $repairOrder->status->label(),
                    ],
                    'problemDescription' => $repairOrder->problem_description,
                    'vehicle' => $repairOrder->vehicle
                        ? [
                            'id' => $repairOrder->vehicle->id,
                            'brand' => $repairOrder->vehicle->brand,
                            'model' => $repairOrder->vehicle->model,
                            'year' => $repairOrder->vehicle->year,
                            'licensePlate' => $repairOrder->vehicle->license_plate,
                        ]
                        : null,
                    'openedAt' => $repairOrder->opened_at->toISOString(),
                    'createdAt' => $repairOrder->created_at->toISOString(),
                    'showUrl' => route('dashboard.repair-orders.show', $repairOrder),
                ])
                ->all(),
        ];
    }
}
