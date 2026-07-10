<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Shared\ValueObjects\Phone;
use App\Models\Customer;
use App\Models\WorkshopUser;
use Illuminate\Validation\ValidationException;

class UpdateCustomerAction
{
    /**
     * @param  array{name?: string|null, phone: string}  $data
     */
    public function handle(WorkshopUser $activeWorkshopUser, Customer $customer, array $data): Customer
    {
        $customer = Customer::query()
            ->whereKey($customer->id)
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->firstOrFail();

        $phone = $this->nullableTrim($data['phone']) ?? '';
        $phoneValue = new Phone($phone);
        $phoneNormalized = $phoneValue->normalize();
        $normalizedPhone = $phoneValue->normalizeLegacyDigits();

        $duplicateExists = Customer::query()
            ->where('workshop_id', $activeWorkshopUser->workshop_id)
            ->whereKeyNot($customer->id)
            ->where(function ($query) use ($phoneNormalized, $normalizedPhone): void {
                $query
                    ->where('phone_normalized', $phoneNormalized)
                    ->orWhere('normalized_phone', $normalizedPhone);
            })
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'phone' => 'A customer with this phone already exists in this workshop.',
            ]);
        }

        $customer->fill([
            'name' => $this->nullableTrim($data['name'] ?? null),
            'phone' => $phone,
            'phone_normalized' => $phoneNormalized,
            'normalized_phone' => $normalizedPhone,
        ]);

        $customer->save();

        return $customer;
    }

    private function nullableTrim(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
