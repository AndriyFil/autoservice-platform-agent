<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'customer_id' => Customer::factory(),
            'brand' => fake()->randomElement(['Opel', 'Toyota', 'Honda', 'Ford', 'Skoda']),
            'model' => fake()->word(),
            'license_plate' => strtoupper(fake()->bothify('??####??')),
        ];
    }
}
