<?php

namespace Tests\Unit;

use App\Domain\RepairOrders\Enums\RepairOrderLineType;
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

    public function test_subtotal_rounds_half_cent_up(): void
    {
        // 0.50 * 1 cent = 0.5 cent, must round up to 1.
        $line = $this->line(['quantity' => '0.50', 'unit_price_cents' => 1, 'tax_rate' => '0.00']);

        $this->assertSame(1, $line->subtotalCents());
    }

    public function test_subtotal_rounds_below_half_cent_down(): void
    {
        // 0.49 * 1 cent = 0.49 cent, must round down to 0.
        $line = $this->line(['quantity' => '0.49', 'unit_price_cents' => 1, 'tax_rate' => '0.00']);

        $this->assertSame(0, $line->subtotalCents());
    }

    public function test_tax_rounds_half_cent_up(): void
    {
        // subtotal 1 cent at 50% tax = 0.5 cent tax, rounds up to 1.
        $line = $this->line(['quantity' => '0.50', 'unit_price_cents' => 1, 'tax_rate' => '50.00']);

        $this->assertSame(1, $line->subtotalCents());
        $this->assertSame(1, $line->taxCents());
        $this->assertSame(2, $line->totalCents());
    }

    public function test_discount_applies_symmetric_negative_rounding(): void
    {
        // Discount subtotal -1 cent at 50% tax = -0.5 cent, rounds away from
        // zero to -1 (same half-up magnitude as a positive line).
        $line = $this->line([
            'type' => RepairOrderLineType::Discount,
            'quantity' => '1.00',
            'unit_price_cents' => 1,
            'tax_rate' => '50.00',
        ]);

        $this->assertSame(-1, $line->subtotalCents());
        $this->assertSame(-1, $line->taxCents());
        $this->assertSame(-2, $line->totalCents());
    }

    public function test_large_values_do_not_overflow(): void
    {
        $line = $this->line([
            'quantity' => '1.00',
            'unit_price_cents' => 2147483647,
            'tax_rate' => '0.00',
        ]);

        $this->assertSame(2147483647, $line->subtotalCents());
        $this->assertSame(0, $line->taxCents());
        $this->assertSame(2147483647, $line->totalCents());
    }

    public function test_zero_price_yields_zero_totals(): void
    {
        $line = $this->line(['quantity' => '1.00', 'unit_price_cents' => 0, 'tax_rate' => '20.00']);

        $this->assertSame(0, $line->subtotalCents());
        $this->assertSame(0, $line->taxCents());
        $this->assertSame(0, $line->totalCents());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function line(array $attributes): RepairOrderLine
    {
        return RepairOrderLine::factory()->create([
            'repair_order_id' => RepairOrder::factory(),
            'type' => RepairOrderLineType::Labor,
            'description' => 'Line',
            ...$attributes,
        ]);
    }
}
