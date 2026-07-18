<?php

namespace App\Domain\RepairOrders\Queries;

use App\Domain\BookingRequests\Services\CustomerMatcher;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkshopUser;

class RepairOrderFormQuery
{
    public function __construct(
        private readonly CustomerMatcher $customerMatcher,
    ) {}

    /**
     * @return array{
     *     customers: array<int, array{
     *         id: int,
     *         name: string|null,
     *         phone: string,
     *         phoneNormalized: string,
     *         vehicles: array<int, array{id: int, brand: string|null, model: string|null, year: int|null, licensePlate: string|null}>
     *     }>,
     *     defaults: array{customer_id: string, customer_name: string, customer_phone: string, vehicle_id: string, new_vehicle: array{make: string, model: string, year: int|null, plate: string}, problem_description: string, booking_request_id: string, requires_estimate_approval: bool},
     *     sourceBookingRequest: array{id: int, customerName: string|null, customerPhone: string|null, problemDescription: string|null, originalMessage: string|null, preferredDate: string|null, existingCustomer: array{id: int, name: string|null, phone: string}|null}|null,
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
            ? $this->customerMatcher->matchBookingRequest($activeWorkshopUser, $sourceBookingRequest)
            : null;
        $vehicleDefaults = $this->vehicleDefaults($sourceBookingRequest, $existingCustomer);

        return [
            'customers' => $customers,
            'defaults' => [
                'customer_id' => $existingCustomer ? (string) $existingCustomer->id : '',
                'customer_name' => $sourceBookingRequest ? ($sourceBookingRequest->customer_name ?? '') : '',
                'customer_phone' => $sourceBookingRequest ? ($sourceBookingRequest->customer_phone ?? '') : '',
                'vehicle_id' => $vehicleDefaults['vehicle_id'],
                'new_vehicle' => $vehicleDefaults['new_vehicle'],
                'problem_description' => $sourceBookingRequest
                    ? ($this->bookingRequestProblemDescription($sourceBookingRequest) ?? '')
                    : '',
                'booking_request_id' => $sourceBookingRequest ? (string) $sourceBookingRequest->id : '',
                'requires_estimate_approval' => true,
            ],
            'sourceBookingRequest' => $sourceBookingRequest
                ? [
                    'id' => $sourceBookingRequest->id,
                    'customerName' => $sourceBookingRequest->customer_name,
                    'customerPhone' => $sourceBookingRequest->customer_phone,
                    'problemDescription' => $sourceBookingRequest->problem_description,
                    'originalMessage' => $sourceBookingRequest->original_message,
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

    /**
     * @return array{vehicle_id: string, new_vehicle: array{make: string, model: string, year: int|null, plate: string}}
     */
    private function vehicleDefaults(?BookingRequest $bookingRequest, ?Customer $customer): array
    {
        $emptyNewVehicle = [
            'make' => '',
            'model' => '',
            'year' => null,
            'plate' => '',
        ];

        if (! $bookingRequest) {
            return ['vehicle_id' => '', 'new_vehicle' => $emptyNewVehicle];
        }

        if ($customer && $bookingRequest->vehicle_id) {
            $linkedVehicle = $customer->vehicles->firstWhere('id', $bookingRequest->vehicle_id);

            if ($linkedVehicle) {
                return ['vehicle_id' => (string) $linkedVehicle->id, 'new_vehicle' => $emptyNewVehicle];
            }
        }

        $requestVehicle = [
            'make' => $this->nullableTrim($bookingRequest->vehicle_brand),
            'model' => $this->nullableTrim($bookingRequest->vehicle_model),
            'year' => $bookingRequest->vehicle_year,
            'plate' => $this->nullableTrim($bookingRequest->vehicle_license_plate),
        ];
        $hasRequestVehicle = collect($requestVehicle)->contains(fn ($value): bool => $value !== null);

        if ($hasRequestVehicle) {
            $matchingVehicle = $customer?->vehicles->first(
                fn (Vehicle $vehicle): bool => $this->vehicleMatchesRequest($vehicle, $requestVehicle),
            );

            if ($matchingVehicle) {
                return ['vehicle_id' => (string) $matchingVehicle->id, 'new_vehicle' => $emptyNewVehicle];
            }

            return [
                'vehicle_id' => '',
                'new_vehicle' => [
                    'make' => $requestVehicle['make'] ?? '',
                    'model' => $requestVehicle['model'] ?? '',
                    'year' => $requestVehicle['year'],
                    'plate' => $requestVehicle['plate'] ?? '',
                ],
            ];
        }

        $onlyVehicle = $customer?->vehicles->count() === 1
            ? $customer->vehicles->first()
            : null;

        return [
            'vehicle_id' => $onlyVehicle ? (string) $onlyVehicle->id : '',
            'new_vehicle' => $emptyNewVehicle,
        ];
    }

    /**
     * @param  array{make: string|null, model: string|null, year: int|null, plate: string|null}  $requestVehicle
     */
    private function vehicleMatchesRequest(Vehicle $vehicle, array $requestVehicle): bool
    {
        return ($requestVehicle['make'] === null || $this->normalizeText($vehicle->brand) === $this->normalizeText($requestVehicle['make']))
            && ($requestVehicle['model'] === null || $this->normalizeText($vehicle->model) === $this->normalizeText($requestVehicle['model']))
            && ($requestVehicle['year'] === null || $vehicle->year === $requestVehicle['year'])
            && ($requestVehicle['plate'] === null || $this->normalizePlate($vehicle->license_plate) === $this->normalizePlate($requestVehicle['plate']));
    }

    private function normalizeText(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function normalizePlate(?string $value): string
    {
        return preg_replace('/[\s-]+/u', '', $this->normalizeText($value)) ?? '';
    }

    private function bookingRequestProblemDescription(BookingRequest $bookingRequest): ?string
    {
        return $this->nullableTrim($bookingRequest->problem_description)
            ?? $this->nullableTrim($bookingRequest->original_message);
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
