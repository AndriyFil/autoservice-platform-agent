<?php

namespace App\Queries\Dashboard;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\WorkshopUser;
use App\Support\Phone;

class DashboardBookingRequestDetailsQuery
{
    /**
     * @return array{
     *     bookingRequest: array{
     *         id: int,
     *         identifier: string,
     *         customerName: string|null,
     *         customerPhone: string,
     *         customerPhoneNormalized: string|null,
     *         problemDescription: string,
     *         originalMessage: string|null,
     *         preferredDate: string|null,
     *         status: array{value: string, label: string},
     *         vehicle: array{brand: string|null, model: string|null, year: int|null, licensePlate: string|null}|null,
     *         extractedData: array{phone: string, customerName: string|null, vehicle: string|null, preferredDate: string|null, summary: string|null},
     *         createdAt: string,
     *         updatedAt: string
     *     },
     *     matchedCustomer: array{id: int, name: string|null, phone: string, showUrl: string}|null,
     *     matchedCustomerVehicles: array<int, array{id: int, brand: string|null, model: string|null, year: int|null, licensePlate: string|null}>,
     *     linkedRepairOrder: array{id: int, status: array{value: string, label: string}, showUrl: string}|null,
     *     canCreateRepairOrder: bool,
     *     availableStatusTransitions: array<int, array{value: string, label: string}>,
     *     customerCreationNotice: string|null
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser, BookingRequest $bookingRequest): array
    {
        $bookingRequest = BookingRequest::query()
            ->with(['vehicle', 'repairOrder'])
            ->whereKey($bookingRequest->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        $matchedCustomer = $this->findCustomerByBookingRequestPhone($activeWorkshopUser, $bookingRequest);
        $linkedRepairOrder = $bookingRequest->repairOrder;

        return [
            'bookingRequest' => [
                'id' => $bookingRequest->id,
                'identifier' => '#'.$bookingRequest->id,
                'customerName' => $bookingRequest->customer_name,
                'customerPhone' => $bookingRequest->customer_phone,
                'customerPhoneNormalized' => $bookingRequest->customer_phone_normalized,
                'problemDescription' => $bookingRequest->problem_description,
                'originalMessage' => $bookingRequest->original_message,
                'preferredDate' => $bookingRequest->preferred_date?->toDateString(),
                'status' => [
                    'value' => $bookingRequest->status->value,
                    'label' => $bookingRequest->status->label(),
                ],
                'vehicle' => $bookingRequest->vehicle
                    ? [
                        'brand' => $bookingRequest->vehicle->brand,
                        'model' => $bookingRequest->vehicle->model,
                        'year' => $bookingRequest->vehicle->year,
                        'licensePlate' => $bookingRequest->vehicle->license_plate,
                    ]
                    : null,
                'extractedData' => [
                    'phone' => $bookingRequest->customer_phone,
                    'customerName' => $bookingRequest->customer_name,
                    'vehicle' => $this->vehicleSummary($bookingRequest),
                    'preferredDate' => $bookingRequest->preferred_date?->toDateString(),
                    'summary' => $this->nullableTrim($bookingRequest->problem_description),
                ],
                'createdAt' => $bookingRequest->created_at->toISOString(),
                'updatedAt' => $bookingRequest->updated_at->toISOString(),
            ],
            'matchedCustomer' => $matchedCustomer
                ? [
                    'id' => $matchedCustomer->id,
                    'name' => $matchedCustomer->name,
                    'phone' => $matchedCustomer->phone,
                    'showUrl' => route('customers.show', $matchedCustomer),
                ]
                : null,
            'matchedCustomerVehicles' => $matchedCustomer
                ? $matchedCustomer->vehicles
                    ->map(fn ($vehicle): array => [
                        'id' => $vehicle->id,
                        'brand' => $vehicle->brand,
                        'model' => $vehicle->model,
                        'year' => $vehicle->year,
                        'licensePlate' => $vehicle->license_plate,
                    ])
                    ->all()
                : [],
            'linkedRepairOrder' => $linkedRepairOrder
                ? [
                    'id' => $linkedRepairOrder->id,
                    'status' => [
                        'value' => $linkedRepairOrder->status->value,
                        'label' => $linkedRepairOrder->status->label(),
                    ],
                    'showUrl' => route('dashboard.repair-orders.show', $linkedRepairOrder),
                ]
                : null,
            'canCreateRepairOrder' => $this->canCreateRepairOrder($bookingRequest),
            'availableStatusTransitions' => $this->availableStatusTransitions($bookingRequest->status),
            'customerCreationNotice' => $matchedCustomer
                ? null
                : 'No existing customer found. A new customer will be created when a repair order is created.',
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

    private function canCreateRepairOrder(BookingRequest $bookingRequest): bool
    {
        return $bookingRequest->repairOrder === null
            && in_array($bookingRequest->status, [BookingRequestStatus::New, BookingRequestStatus::Confirmed], true);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function availableStatusTransitions(BookingRequestStatus $status): array
    {
        return collect(BookingRequestStatus::cases())
            ->filter(fn (BookingRequestStatus $targetStatus): bool => $status->canTransitionTo($targetStatus))
            ->map(fn (BookingRequestStatus $targetStatus): array => [
                'value' => $targetStatus->value,
                'label' => $targetStatus->label(),
            ])
            ->values()
            ->all();
    }

    private function vehicleSummary(BookingRequest $bookingRequest): ?string
    {
        if (! $bookingRequest->vehicle) {
            return null;
        }

        $summary = collect([
            $bookingRequest->vehicle->brand,
            $bookingRequest->vehicle->model,
            $bookingRequest->vehicle->year,
            $bookingRequest->vehicle->license_plate,
        ])
            ->filter(fn ($part): bool => filled($part))
            ->implode(' ');

        return $summary === '' ? null : $summary;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
