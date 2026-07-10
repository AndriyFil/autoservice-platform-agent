<?php

namespace App\Domain\Customers\Queries;

use App\Domain\Shared\ValueObjects\Phone;
use App\Models\Customer;
use App\Models\WorkshopUser;
use Illuminate\Support\Carbon;

class CustomerIndexQuery
{
    /**
     * @return array<int, array{
     *     id: int,
     *     name: string|null,
     *     phone: string,
     *     vehiclesCount: int,
     *     bookingRequestsCount: int,
     *     latestBookingRequestDate: string|null
     * }>
     */
    public function handle(WorkshopUser $activeWorkshopUser, ?string $search = null): array
    {
        return Customer::query()
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->when($this->nullableTrim($search), function ($query, string $search): void {
                $normalizedSearch = (new Phone($search))->normalize();
                $legacyDigitsSearch = (new Phone($search))->normalizeLegacyDigits();

                $query->where(function ($query) use ($search, $normalizedSearch, $legacyDigitsSearch): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('phone_normalized', 'like', "%{$search}%")
                        ->orWhere('normalized_phone', 'like', "%{$search}%")
                        ->orWhere('phone_normalized', 'like', "%{$normalizedSearch}%")
                        ->orWhere('normalized_phone', 'like', "%{$legacyDigitsSearch}%");
                });
            })
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

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
