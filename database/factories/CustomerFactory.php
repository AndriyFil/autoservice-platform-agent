<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Workshop;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = fake()->numerify('+1 (###) ###-####');
        $phoneValue = new Phone($phone);

        return [
            'workshop_id' => Workshop::factory(),
            'name' => fake()->name(),
            'phone' => $phone,
            'phone_normalized' => $phoneValue->normalize(),
            'normalized_phone' => $phoneValue->normalizeLegacyDigits(),
        ];
    }
}
