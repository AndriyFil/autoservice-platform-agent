<?php

namespace Database\Factories;

use App\Enums\RepairOrderLineType;
use App\Models\RepairOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepairOrderLine>
 */
class RepairOrderLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repair_order_id' => RepairOrder::factory(),
            'type' => RepairOrderLineType::Labor,
            'description' => fake()->sentence(),
            'quantity' => '1.00',
            'unit_price_cents' => 0,
            'tax_rate' => '0.00',
            'sort_order' => 0,
        ];
    }
}
