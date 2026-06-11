<?php

namespace App\Queries\Dashboard;

use App\Models\BookingRequest;
use App\Models\WorkshopUser;

class DashboardBookingRequestDetailsQuery
{
    /**
     * @return array{
     *     id: int,
     *     customerName: string,
     *     customerPhone: string,
     *     problemDescription: string,
     *     preferredDate: string|null,
     *     status: array{value: string, label: string},
     *     vehicle: array{brand: string|null, model: string|null, licensePlate: string|null}|null,
     *     createdAt: string,
     *     updatedAt: string
     * }
     */
    public function handle(WorkshopUser $activeWorkshopUser, BookingRequest $bookingRequest): array
    {
        $bookingRequest = BookingRequest::query()
            ->with('vehicle')
            ->whereKey($bookingRequest->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        return [
            'id' => $bookingRequest->id,
            'customerName' => $bookingRequest->customer_name,
            'customerPhone' => $bookingRequest->customer_phone,
            'problemDescription' => $bookingRequest->problem_description,
            'preferredDate' => $bookingRequest->preferred_date?->toDateString(),
            'status' => [
                'value' => $bookingRequest->status->value,
                'label' => $bookingRequest->status->label(),
            ],
            'vehicle' => $bookingRequest->vehicle
                ? [
                    'brand' => $bookingRequest->vehicle->brand,
                    'model' => $bookingRequest->vehicle->model,
                    'licensePlate' => $bookingRequest->vehicle->license_plate,
                ]
                : null,
            'createdAt' => $bookingRequest->created_at->toISOString(),
            'updatedAt' => $bookingRequest->updated_at->toISOString(),
        ];
    }
}
