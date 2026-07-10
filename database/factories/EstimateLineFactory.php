<?php

namespace Database\Factories;

use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Models\Estimate;
use App\Models\EstimateLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EstimateLine>
 */
class EstimateLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estimate_id' => Estimate::factory(),
            'type' => RepairOrderLineType::Labor,
            'description' => fake()->sentence(),
            'quantity' => '1.00',
            'unit_price_cents' => fake()->numberBetween(1000, 50000),
            'tax_rate' => '20.00',
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'sort_order' => 0,
        ];
    }
}
