<?php

namespace App\Queries\Customers;

use App\Models\Customer;
use App\Models\WorkshopUser;
use Illuminate\Support\Carbon;

class CustomerListQuery
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string,
     *     phone: string,
     *     vehiclesCount: int,
     *     bookingRequestsCount: int,
     *     latestBookingRequestDate: string|null
     * }>
     */
    public function handle(WorkshopUser $activeWorkshopUser): array
    {
        return Customer::query()
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->withCount(['vehicles', 'bookingRequests'])
            ->withMax('bookingRequests', 'created_at')
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'vehiclesCount' => $customer->vehicles_count,
                'bookingRequestsCount' => $customer->booking_requests_count,
                'latestBookingRequestDate' => $customer->getAttribute('booking_requests_max_created_at')
                    ? Carbon::parse($customer->getAttribute('booking_requests_max_created_at'))->toISOString()
                    : null,
            ])
            ->all();
    }
}
