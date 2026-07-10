<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Enums\RepairOrderLineType;
use App\Enums\RepairOrderStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RepairOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_order_belongs_to_workshop(): void
    {
        $workshop = Workshop::factory()->create();
        $repairOrder = RepairOrder::factory()->forWorkshop($workshop)->create();

        $this->assertTrue($repairOrder->workshop->is($workshop));
    }

    public function test_repair_order_can_optionally_link_to_booking_request(): void
    {
        $workshop = Workshop::factory()->create();
        $customer = Customer::factory()->for($workshop)->create();
        $bookingRequest = BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'problem_description' => 'Check engine light.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::Confirmed,
        ]);

        $linkedRepairOrder = RepairOrder::factory()->forCustomer($customer)->create([
            'booking_request_id' => $bookingRequest->id,
        ]);
        $manualRepairOrder = RepairOrder::factory()->forCustomer($customer)->create([
            'booking_request_id' => null,
        ]);

        $this->assertTrue($linkedRepairOrder->bookingRequest->is($bookingRequest));
        $this->assertNull($manualRepairOrder->bookingRequest);
    }

    public function test_repair_order_has_many_estimate_lines(): void
    {
        $repairOrder = RepairOrder::factory()->create();

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'sort_order' => 2,
        ]);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Fee,
            'sort_order' => 1,
        ]);

        $this->assertCount(2, $repairOrder->lines);
        $this->assertSame(RepairOrderLineType::Fee, $repairOrder->lines->first()->type);
    }

    public function test_draft_repair_order_can_exist_without_invoice(): void
    {
        $repairOrder = RepairOrder::factory()->create([
            'status' => RepairOrderStatus::Draft,
        ]);

        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->status);
        $this->assertFalse(Schema::hasTable('invoices'));
    }

    public function test_public_intake_does_not_create_repair_order_automatically(): void
    {
        $workshop = Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->post('/w/main-auto/intake', [
            'message' => 'Opel Insignia, check engine light came on, when can I come?',
            'phone' => '+38 (050) 111-22-33',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('booking_requests', 1);
        $this->assertDatabaseHas('booking_requests', [
            'workshop_id' => $workshop->id,
        ]);
        $this->assertDatabaseCount('repair_orders', 0);
    }
}
