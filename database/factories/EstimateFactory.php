<?php

namespace Database\Factories;

use App\Enums\EstimateStatus;
use App\Models\Estimate;
use App\Models\RepairOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estimate>
 */
class EstimateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repair_order_id' => RepairOrder::factory(),
            'version' => 1,
            'status' => EstimateStatus::Draft,
            'subtotal_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 0,
            'currency' => 'USD',
            'requires_customer_approval' => false,
            'created_by_user_id' => null,
            'generated_at' => null,
            'approved_at' => null,
            'rejected_at' => null,
            'superseded_at' => null,
        ];
    }
}
