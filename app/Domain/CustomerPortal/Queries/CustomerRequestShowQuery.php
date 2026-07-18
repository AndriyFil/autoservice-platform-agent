<?php

namespace App\Domain\CustomerPortal\Queries;

use App\Models\BookingRequest;

class CustomerRequestShowQuery
{
    /** @return array<string, mixed> */
    public function handle(string $verifiedPhone, int $bookingRequestId): array
    {
        $request = BookingRequest::query()
            ->whereKey($bookingRequestId)
            ->where('customer_phone_normalized', $verifiedPhone)
            ->with([
                'workshop:id,name',
                'repairOrder:id,booking_request_id,status,opened_at,created_at,updated_at',
            ])
            ->firstOrFail();

        $title = trim((string) ($request->problem_description ?: $request->original_message));

        return [
            'id' => $request->id,
            'title' => $title !== '' ? $title : 'Service request',
            'problemDescription' => $request->problem_description ?: $request->original_message,
            'status' => ['value' => $request->status->value, 'label' => $request->status->label()],
            'workshopName' => $request->workshop->name,
            'submittedAt' => $request->created_at->toIso8601String(),
            'updatedAt' => $request->updated_at->toIso8601String(),
            'customerName' => $request->customer_name,
            'vehicle' => array_filter([
                'brand' => $request->vehicle_brand,
                'model' => $request->vehicle_model,
                'year' => $request->vehicle_year,
                'licensePlate' => $request->vehicle_license_plate,
            ], static fn ($value): bool => $value !== null && $value !== ''),
            'repairOrder' => $request->repairOrder
                ? [
                    'id' => $request->repairOrder->id,
                    'status' => [
                        'value' => $request->repairOrder->status->value,
                        'label' => $request->repairOrder->status->label(),
                    ],
                    'openedAt' => ($request->repairOrder->opened_at ?? $request->repairOrder->created_at)->toIso8601String(),
                    'updatedAt' => $request->repairOrder->updated_at->toIso8601String(),
                ]
                : null,
        ];
    }
}
