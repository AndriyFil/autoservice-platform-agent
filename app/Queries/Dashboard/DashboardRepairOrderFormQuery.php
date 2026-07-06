<?php

namespace App\Queries\Dashboard;

use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\WorkshopUser;
use App\Support\Phone;

class DashboardRepairOrderFormQuery
{
    /**
     * @return array{
     *     customers: array<int, array{
     *         id: int,
     *         name: string|null,
     *         phone: string,
     *         phoneNormalized: string,
     *         vehicles: array<int, array{id: int, brand: string|null, model: string|null, year: int|null, licensePlate: string|null}>
     *     }>,
     *     defaults: array{customer_id: string, customer_name: string, customer_phone: string, vehicle_id: string, problem_description: string, booking_request_id: string},
     *     sourceBookingRequest: array{id: int, customerName: string|null, customerPhone: string|null, preferredDate: string|null, existingCustomer: array{id: int, name: string|null, phone: string}|null}|null,
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
                'phoneNormalized' => $customer->phone_normalized,
                'vehicles' => $customer->vehicles
                    ->map(fn ($vehicle): array => [
                        'id' => $vehicle->id,
                        'brand' => $vehicle->brand,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'licensePlate' => $vehicle->license_plate,
                    ])
                    ->all(),
            ])
            ->all();
        $existingCustomer = $sourceBookingRequest
            ? $this->findCustomerByBookingRequestPhone($activeWorkshopUser, $sourceBookingRequest)
            : null;
        $existingCustomerVehiclesCount = $existingCustomer?->vehicles->count() ?? 0;

        return [
            'customers' => $customers,
            'defaults' => [
                'customer_id' => $existingCustomer ? (string) $existingCustomer->id : '',
                'customer_name' => $sourceBookingRequest ? ($sourceBookingRequest->customer_name ?? '') : '',
                'customer_phone' => $sourceBookingRequest ? ($sourceBookingRequest->customer_phone ?? '') : '',
                'vehicle_id' => $existingCustomerVehiclesCount === 1 ? (string) $existingCustomer->vehicles->first()->id : '',
                'problem_description' => $sourceBookingRequest->problem_description ?? '',
                'booking_request_id' => $sourceBookingRequest ? (string) $sourceBookingRequest->id : '',
            ],
            'sourceBookingRequest' => $sourceBookingRequest
                ? [
                    'id' => $sourceBookingRequest->id,
                    'customerName' => $sourceBookingRequest->customer_name,
                    'customerPhone' => $sourceBookingRequest->customer_phone,
                    'preferredDate' => $sourceBookingRequest->preferred_date?->toDateString(),
                    'existingCustomer' => $existingCustomer
                        ? [
                            'id' => $existingCustomer->id,
                            'name' => $existingCustomer->name,
                            'phone' => $existingCustomer->phone,
                        ]
                        : null,
                ]
                : null,
            'existingRepairOrderId' => $sourceBookingRequest?->repairOrder?->id,
        ];
    }

    private function findCustomerByBookingRequestPhone(
        WorkshopUser $activeWorkshopUser,
        BookingRequest $bookingRequest,
    ): ?Customer {
        $phone = trim((string) $bookingRequest->customer_phone);

        if ($phone === '') {
            return null;
        }

        return Customer::query()
            ->with(['vehicles' => fn ($query) => $query
                ->orderBy('brand')
                ->orderBy('model')
                ->orderBy('id'),
            ])
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->where('phone_normalized', $bookingRequest->customer_phone_normalized
                ?: (new Phone($phone))->normalize())
            ->first();
    }
}
