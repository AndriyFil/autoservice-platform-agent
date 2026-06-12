<?php

namespace App\Queries\Customers;

use App\Models\Customer;
use App\Models\WorkshopUser;
use App\Support\PhoneNormalizer;

class SearchActiveWorkshopCustomersQuery
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer,
    ) {}

    /**
     * @return array<int, array{id: int, name: string, phone: string}>
     */
    public function handle(WorkshopUser $activeWorkshopUser, string $query): array
    {
        $normalizedQuery = $this->phoneNormalizer->normalize($query);

        return Customer::query()
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->where(function ($customers) use ($query, $normalizedQuery): void {
                $customers->whereLike('name', "%{$query}%");

                if ($normalizedQuery !== '') {
                    $customers
                        ->orWhere('normalized_phone', 'like', "%{$normalizedQuery}%")
                        ->orWhere('phone', 'like', "%{$normalizedQuery}%");
                }
            })
            ->orderBy('name')
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'name', 'phone'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ])
            ->all();
    }
}
