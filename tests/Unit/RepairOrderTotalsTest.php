<?php

namespace Tests\Unit;

use App\Enums\RepairOrderLineType;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepairOrderTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_order_lines_calculate_totals_with_integer_cents(): void
    {
        $repairOrder = RepairOrder::factory()->create();

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Diagnosis labor',
            'quantity' => '2.50',
            'unit_price_cents' => 10000,
            'tax_rate' => '20.00',
        ]);

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Part,
            'description' => 'Sensor',
            'quantity' => '1.00',
            'unit_price_cents' => 12345,
            'tax_rate' => '0.00',
        ]);

        $repairOrder->load('lines');

        $this->assertSame(37345, $repairOrder->subtotalCents());
        $this->assertSame(5000, $repairOrder->taxCents());
        $this->assertSame(42345, $repairOrder->totalCents());
    }

    public function test_discount_line_reduces_repair_order_total(): void
    {
        $repairOrder = RepairOrder::factory()->create();

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Labor',
            'quantity' => '1.00',
            'unit_price_cents' => 15000,
        ]);

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Discount,
            'description' => 'Goodwill discount',
            'quantity' => '1.00',
            'unit_price_cents' => 2500,
        ]);

        $repairOrder->load('lines');

        $this->assertSame(12500, $repairOrder->subtotalCents());
        $this->assertSame(0, $repairOrder->taxCents());
        $this->assertSame(12500, $repairOrder->totalCents());
    }
}
