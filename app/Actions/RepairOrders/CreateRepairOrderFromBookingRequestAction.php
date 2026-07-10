<?php

namespace App\Actions\RepairOrders;

use App\Domain\Shared\ValueObjects\Phone;
use App\Enums\BookingRequestStatus;
use App\Enums\RepairOrderStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\Vehicle;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateRepairOrderFromBookingRequestAction
{
    /**
     * @param  array{
     *     customer_name?: string|null,
     *     vehicle_id?: int|null,
     *     booking_request_id: int,
     *     requires_estimate_approval?: bool,
     *     notes?: string|null,
     *     new_vehicle?: array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}
     * }  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, array $data): RepairOrder
    {
        return DB::transaction(function () use ($activeWorkshopUser, $data): RepairOrder {
            $bookingRequest = $this->resolveBookingRequest($activeWorkshopUser, $data['booking_request_id']);
            $customer = $this->resolveCustomer($activeWorkshopUser, $bookingRequest, $data['customer_name'] ?? null);
            $vehicleId = $this->resolveVehicleId($activeWorkshopUser, $customer, $data);

            return RepairOrder::create([
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleId,
                'booking_request_id' => $bookingRequest->id,
                'status' => RepairOrderStatus::Draft,
                'requires_estimate_approval' => $data['requires_estimate_approval'] ?? true,
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $activeWorkshopUser->user_id,
                'problem_description' => $this->bookingRequestProblemDescription($bookingRequest),
                'opened_at' => now(),
                'closed_at' => null,
            ]);
        });
    }

    private function resolveBookingRequest(WorkshopUser $activeWorkshopUser, int $bookingRequestId): BookingRequest
    {
        $bookingRequest = BookingRequest::query()
            ->with('repairOrder')
            ->whereKey($bookingRequestId)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        if ($bookingRequest->status !== BookingRequestStatus::Confirmed) {
            throw new DomainException('Repair order can be created only from a confirmed booking request.');
        }

        if ($bookingRequest->repairOrder) {
            throw new DomainException('This booking request already has a repair order.');
        }

        return $bookingRequest;
    }

    private function resolveCustomer(
        WorkshopUser $activeWorkshopUser,
        BookingRequest $bookingRequest,
        ?string $submittedCustomerName,
    ): Customer {
        $phone = trim((string) $bookingRequest->customer_phone);

        if ($phone === '') {
            throw new DomainException('Booking request phone is required to create a repair order.');
        }

        return Customer::firstOrCreate(
            [
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'phone_normalized' => $bookingRequest->customer_phone_normalized
                    ?: (new Phone($phone))->normalize(),
            ],
            [
                'name' => $this->nullableTrim($submittedCustomerName ?? $bookingRequest->customer_name),
                'phone' => $phone,
                'normalized_phone' => (new Phone($phone))->normalizeLegacyDigits(),
            ],
        );
    }

    /**
     * @param  array{
     *     vehicle_id?: int|null,
     *     new_vehicle?: array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}
     * }  $data
     */
    private function resolveVehicleId(WorkshopUser $activeWorkshopUser, Customer $customer, array $data): ?int
    {
        $vehicleId = $data['vehicle_id'] ?? null;

        if ($vehicleId !== null) {
            $vehicle = Vehicle::query()
                ->whereKey($vehicleId)
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (! $vehicle) {
                throw new DomainException('The selected vehicle does not belong to this customer.');
            }

            return $vehicle->id;
        }

        $newVehicle = $data['new_vehicle'] ?? [];

        if (! $this->hasNewVehicleData($newVehicle)) {
            return null;
        }

        return Vehicle::create([
            'workshop_id' => $activeWorkshopUser->workshop_id,
            'customer_id' => $customer->id,
            'brand' => $this->nullableTrim($newVehicle['make'] ?? null),
            'model' => $this->nullableTrim($newVehicle['model'] ?? null),
            'year' => $newVehicle['year'] ?? null,
            'license_plate' => $this->nullableTrim($newVehicle['plate'] ?? null),
        ])->id;
    }

    /**
     * @param  array{make?: string|null, model?: string|null, year?: int|null, plate?: string|null}  $newVehicle
     */
    private function hasNewVehicleData(array $newVehicle): bool
    {
        return $this->nullableTrim($newVehicle['make'] ?? null) !== null
            || $this->nullableTrim($newVehicle['model'] ?? null) !== null
            || ($newVehicle['year'] ?? null) !== null
            || $this->nullableTrim($newVehicle['plate'] ?? null) !== null;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function bookingRequestProblemDescription(BookingRequest $bookingRequest): string
    {
        return $this->nullableTrim($bookingRequest->problem_description)
            ?? $this->nullableTrim($bookingRequest->original_message)
            ?? '';
    }
}
