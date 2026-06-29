<?php

namespace App\Queries\Admin;

use App\Enums\BookingRequestStatus;
use App\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\WorkshopUser;
use App\Support\Intake\MissingNextIntakeFieldResolver;

class UnassignedIntakeRequestsQuery
{
    public function __construct(
        private readonly MissingNextIntakeFieldResolver $missingNextFieldResolver,
    ) {}

    /**
     * @return array<int, array{
     *     id: int,
     *     receivedAt: string,
     *     originalMessage: string|null,
     *     problemSummary: string|null,
     *     customerPhone: string|null,
     *     vehicle: null,
     *     missingNextField: array{value: string, label: string}|null,
     *     status: array{value: string, label: string}
     * }>
     */
    public function handle(WorkshopUser $activeWorkshopUser, int $limit = 25): array
    {
        if ($activeWorkshopUser->role !== WorkshopUserRole::Owner) {
            return [];
        }

        return BookingRequest::query()
            ->whereNull('workshop_id')
            ->where('status', BookingRequestStatus::Submitted)
            ->orderBy('created_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (BookingRequest $bookingRequest): array => [
                'id' => $bookingRequest->id,
                'receivedAt' => $bookingRequest->created_at->toISOString(),
                'originalMessage' => $bookingRequest->original_message,
                'problemSummary' => $bookingRequest->problem_description,
                'customerPhone' => $bookingRequest->customer_phone,
                'vehicle' => null,
                'missingNextField' => $this->missingNextField($bookingRequest),
                'status' => [
                    'value' => BookingRequestStatus::Submitted->value,
                    'label' => 'Needs review',
                ],
            ])
            ->all();
    }

    /**
     * @return array{value: string, label: string}|null
     */
    private function missingNextField(BookingRequest $bookingRequest): ?array
    {
        $field = $this->missingNextFieldResolver->resolve(
            phone: $bookingRequest->customer_phone,
            vehicleMake: null,
            vehicleModel: null,
            vehiclePlate: null,
            preferredTimeText: null,
        );

        return $field
            ? [
                'value' => $field->value,
                'label' => $field->label(),
            ]
            : null;
    }
}
