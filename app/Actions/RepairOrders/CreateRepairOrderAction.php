<?php

namespace App\Actions\RepairOrders;

use App\Enums\BookingRequestStatus;
use App\Enums\RepairOrderStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\Vehicle;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateRepairOrderAction
{
    /**
     * @param  array{customer_id: int, vehicle_id?: int|null, booking_request_id?: int|null, problem_description: string, notes?: string|null}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, array $data): RepairOrder
    {
        return DB::transaction(function () use ($activeWorkshopUser, $data): RepairOrder {
            $bookingRequest = null;

            if (! empty($data['booking_request_id'])) {
                $bookingRequest = BookingRequest::query()
                    ->with('repairOrder')
                    ->whereKey($data['booking_request_id'])
                    ->where('workshop_id', $activeWorkshopUser->workshop_id)
                    ->firstOrFail();

                if ($bookingRequest->status !== BookingRequestStatus::Confirmed) {
                    throw new DomainException('Repair order can be created only from a confirmed booking request.');
                }

                if ($bookingRequest->repairOrder) {
                    throw new DomainException('This booking request already has a repair order.');
                }
            }

            $customer = Customer::query()
                ->whereKey($data['customer_id'])
                ->where('workshop_id', $activeWorkshopUser->workshop_id)
                ->firstOrFail();

            $vehicleId = $data['vehicle_id'] ?? null;

            if ($vehicleId) {
                $vehicle = Vehicle::query()
                    ->whereKey($vehicleId)
                    ->where('workshop_id', $activeWorkshopUser->workshop_id)
                    ->where('customer_id', $customer->id)
                    ->first();

                if (! $vehicle) {
                    throw new DomainException('The selected vehicle does not belong to this customer.');
                }
            }

            return RepairOrder::create([
                'workshop_id' => $activeWorkshopUser->workshop_id,
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicleId,
                'booking_request_id' => $bookingRequest?->id,
                'status' => RepairOrderStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by_user_id' => $activeWorkshopUser->user_id,
                'problem_description' => $bookingRequest?->problem_description ?? $data['problem_description'],
                'opened_at' => now(),
                'closed_at' => null,
            ]);
        });
    }
}
