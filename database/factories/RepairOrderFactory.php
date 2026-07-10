<?php

namespace Database\Factories;

use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairOrder>
 */
class RepairOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'customer_id' => Customer::factory(),
            'vehicle_id' => null,
            'booking_request_id' => null,
            'status' => RepairOrderStatus::Draft,
            'requires_estimate_approval' => true,
            'notes' => null,
            'created_by_user_id' => User::factory(),
            'problem_description' => fake()->sentence(),
            'opened_at' => now(),
            'closed_at' => null,
        ];
    }

    public function forWorkshop(Workshop $workshop): static
    {
        return $this->state(fn (): array => [
            'workshop_id' => $workshop->id,
            'customer_id' => Customer::factory()->for($workshop),
            'vehicle_id' => null,
        ]);
    }

    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (): array => [
            'workshop_id' => $customer->workshop_id,
            'customer_id' => $customer->id,
            'vehicle_id' => null,
        ]);
    }

    public function forVehicle(Vehicle $vehicle): static
    {
        return $this->state(fn (): array => [
            'workshop_id' => $vehicle->workshop_id,
            'customer_id' => $vehicle->customer_id,
            'vehicle_id' => $vehicle->id,
        ]);
    }
}
