<?php

namespace App\Queries\Customers;

use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkshopUser;

class CustomerDetailsQuery
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     phone: string,
     *     vehicles: array<int, array{id: int, brand: string|null, model: string|null, licensePlate: string|null}>,
     *     bookingRequests: array<int, array{
     *         id: int,
     *         status: array{value: string, label: string},
     *         problemDescription: string,
     *         preferredDate: string|null,
     *         createdAt: string
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
            ])
            ->whereKey($customer->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'vehicles' => $customer->vehicles
                ->map(fn (Vehicle $vehicle): array => [
                    'id' => $vehicle->id,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
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
                ])
                ->all(),
        ];
    }
}
