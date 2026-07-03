<?php

namespace App\Queries\Dashboard;

use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\WorkshopUser;

class DashboardRepairOrderFormQuery
{
    /**
     * @return array{
     *     customers: array<int, array{
     *         id: int,
     *         name: string,
     *         phone: string,
     *         vehicles: array<int, array{id: int, brand: string|null, model: string|null, licensePlate: string|null}>
     *     }>,
     *     defaults: array{customer_id: string, vehicle_id: string, problem_description: string, booking_request_id: string},
     *     sourceBookingRequest: array{id: int, customerName: string, customerPhone: string, preferredDate: string|null}|null,
     *     existingRepairOrderId: int|null
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser, ?int $bookingRequestId = null): array
    {
        $sourceBookingRequest = $bookingRequestId
            ? BookingRequest::query()
                ->with('repairOrder')
                ->whereKey($bookingRequestId)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->firstOrFail()
            : null;

        $customers = Customer::query()
            ->with(['vehicles' => fn ($query) => $query
                ->orderBy('brand')
                ->orderBy('model')
                ->orderBy('id'),
            ])
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'vehicles' => $customer->vehicles
                    ->map(fn ($vehicle): array => [
                        'id' => $vehicle->id,
                        'brand' => $vehicle->brand,
                        'model' => $vehicle->model,
                        'licensePlate' => $vehicle->license_plate,
                    ])
                    ->all(),
            ])
            ->all();

        return [
            'customers' => $customers,
            'defaults' => [
                'customer_id' => $sourceBookingRequest ? (string) $sourceBookingRequest->customer_id : '',
                'vehicle_id' => $sourceBookingRequest?->vehicle_id ? (string) $sourceBookingRequest->vehicle_id : '',
                'problem_description' => $sourceBookingRequest->problem_description ?? '',
                'booking_request_id' => $sourceBookingRequest ? (string) $sourceBookingRequest->id : '',
            ],
            'sourceBookingRequest' => $sourceBookingRequest
                ? [
                    'id' => $sourceBookingRequest->id,
                    'customerName' => $sourceBookingRequest->customer_name,
                    'customerPhone' => $sourceBookingRequest->customer_phone,
                    'preferredDate' => $sourceBookingRequest->preferred_date?->toDateString(),
                ]
                : null,
            'existingRepairOrderId' => $sourceBookingRequest?->repairOrder?->id,
        ];
    }
}
