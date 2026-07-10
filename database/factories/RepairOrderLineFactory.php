<?php

namespace Database\Factories;

use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepairOrderLine>
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
