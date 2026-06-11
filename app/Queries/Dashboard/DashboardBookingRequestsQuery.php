<?php

namespace App\Queries\Dashboard;

use App\Models\BookingRequest;
use App\Models\Workshop;

class DashboardBookingRequestsQuery
{
    /**
     * @return array<int, array{
     *     id: int,
     *     customerName: string,
     *     customerPhone: string,
     *     problemDescription: string,
     *     preferredDate: string|null,
     *     status: array{value: string, label: string},
     *     vehicle: array{brand: string|null, model: string|null, licensePlate: string|null}|null,
     *     createdAt: string
     * }>
     */
    public function handle(Workshop $workshop): array
    {
        return BookingRequest::query()
            ->with('vehicle')
            ->where('workshop_id', $workshop->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (BookingRequest $bookingRequest): array => [
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
            ])
            ->all();
    }
}
