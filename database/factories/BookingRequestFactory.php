<?php

namespace Database\Factories;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingRequest>
 */
class BookingRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'customer_id' => Customer::factory(),
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('380#########'),
            'problem_description' => fake()->sentence(),
            'original_message' => fake()->sentence(),
            'preferred_date' => null,
            'status' => BookingRequestStatus::New,
        ];
    }
}
