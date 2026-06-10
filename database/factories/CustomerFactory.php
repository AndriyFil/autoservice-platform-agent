<?php

namespace Database\Factories;

use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
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

        return [
            'workshop_id' => Workshop::factory(),
            'name' => fake()->name(),
            'phone' => $phone,
            'normalized_phone' => preg_replace('/\D+/', '', $phone) ?? '',
        ];
    }
}
