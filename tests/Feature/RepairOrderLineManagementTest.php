<?php

namespace Tests\Feature;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\RepairOrderLineType;
use App\Enums\RepairOrderStatus;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepairOrderLineManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_add_labor_part_fee_and_discount_lines_to_active_workshop_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop, WorkshopUserRole::Staff);
        $repairOrder = $this->createRepairOrder($workshop);

        foreach (RepairOrderLineType::cases() as $index => $type) {
            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard.repair-orders.show', $repairOrder))
                ->post(route('dashboard.repair-orders.lines.store', $repairOrder), [
                    'type' => $type->value,
                    'description' => "{$type->label()} line",
                    'quantity' => '1.50',
                    'unit_price_cents' => 12000,
                    'tax_rate' => '20.00',
                    'sort_order' => $index + 1,
                ])
                ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
                ->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('repair_order_lines', 4);
        $this->assertDatabaseHas('repair_order_lines', [
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Discount->value,
            'description' => 'Discount line',
            'unit_price_cents' => 12000,
            'sort_order' => 4,
        ]);
    }

    public function test_staff_can_update_and_delete_repair_order_line(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        $line = RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Old labor',
            'quantity' => '1.00',
            'unit_price_cents' => 10000,
            'tax_rate' => '0.00',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.lines.update', [$repairOrder, $line]), [
                'type' => RepairOrderLineType::Part->value,
                'description' => 'Updated part',
                'quantity' => '2.00',
                'unit_price_cents' => 7500,
                'tax_rate' => '10.00',
                'sort_order' => 3,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('repair_order_lines', [
            'id' => $line->id,
            'type' => RepairOrderLineType::Part->value,
            'description' => 'Updated part',
            'quantity' => '2.00',
            'unit_price_cents' => 7500,
            'tax_rate' => '10.00',
            'sort_order' => 3,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->delete(route('dashboard.repair-orders.lines.destroy', [$repairOrder, $line]))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('repair_order_lines', [
            'id' => $line->id,
        ]);
    }

    public function test_cannot_modify_repair_order_line_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherRepairOrder = $this->createRepairOrder($otherWorkshop);
        $otherLine = RepairOrderLine::factory()->create([
            'repair_order_id' => $otherRepairOrder->id,
            'description' => 'Other workshop line',
            'unit_price_cents' => 5000,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.lines.store', $otherRepairOrder), [
                'type' => RepairOrderLineType::Labor->value,
                'description' => 'Cross workshop line',
                'quantity' => '1.00',
                'unit_price_cents' => 10000,
                'tax_rate' => '0.00',
            ])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('dashboard.repair-orders.lines.update', [$otherRepairOrder, $otherLine]), [
                'type' => RepairOrderLineType::Labor->value,
                'description' => 'Changed cross workshop line',
                'quantity' => '1.00',
                'unit_price_cents' => 10000,
                'tax_rate' => '0.00',
            ])
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->delete(route('dashboard.repair-orders.lines.destroy', [$otherRepairOrder, $otherLine]))
            ->assertNotFound();

        $this->assertDatabaseCount('repair_order_lines', 1);
        $this->assertSame('Other workshop line', $otherLine->refresh()->description);
    }

    public function test_cross_workshop_request_returns_not_found_before_validation(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherRepairOrder = $this->createRepairOrder($otherWorkshop);

        // Invalid payload must not surface validation errors for a foreign
        // resource: authorization (404) has to win over validation (422).
        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.lines.store', $otherRepairOrder), [
                'type' => 'not-a-type',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('repair_order_lines', 0);
    }

    public function test_invalid_repair_order_line_payload_is_rejected(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.lines.store', $repairOrder), [
                'type' => 'diagnosis',
                'description' => '',
                'quantity' => '0.00',
                'unit_price_cents' => -1,
                'tax_rate' => '100.01',
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasErrors(['type', 'description', 'quantity', 'unit_price_cents', 'tax_rate']);

        $this->assertDatabaseCount('repair_order_lines', 0);
    }

    public function test_repair_order_show_exposes_lines_totals_line_types_and_status_actions(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Labor',
            'quantity' => '2.00',
            'unit_price_cents' => 10000,
            'tax_rate' => '20.00',
            'sort_order' => 1,
        ]);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Discount,
            'description' => 'Discount',
            'quantity' => '1.00',
            'unit_price_cents' => 2500,
            'tax_rate' => '0.00',
            'sort_order' => 2,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Show')
                ->has('repairOrder.lines', 2)
                ->where('repairOrder.lines.0.description', 'Labor')
                ->where('repairOrder.lines.0.subtotalCents', 20000)
                ->where('repairOrder.lines.0.taxCents', 4000)
                ->where('repairOrder.lines.0.totalCents', 24000)
                ->where('repairOrder.lines.1.description', 'Discount')
                ->where('repairOrder.lines.1.subtotalCents', -2500)
                ->where('repairOrder.estimateTotals.subtotalCents', 17500)
                ->where('repairOrder.estimateTotals.taxCents', 4000)
                ->where('repairOrder.estimateTotals.totalCents', 21500)
                ->where('repairOrder.availableLineTypes.0.value', 'labor')
                ->where('repairOrder.availableLineTypes.3.value', 'discount')
                ->where('translations.repair_orders.sections.estimates', __('repair_orders.sections.estimates'))
                ->where('translations.repair_orders.actions.create_estimate_pdf', __('repair_orders.actions.create_estimate_pdf'))
                ->where('repairOrder.statusActions.canMarkEstimated', true)
                ->where('repairOrder.statusActions.hasEstimate', false)
                ->where('repairOrder.availableStatusTransitions.0.value', 'in_progress')
                ->where('repairOrder.availableStatusTransitions.1.value', 'cancelled'));
    }

    public function test_draft_repair_order_with_lines_can_create_estimate_pdf_and_become_estimated(): void
    {
        Storage::fake('documents_local');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', __('repair_orders.estimate_created'));

        $this->assertSame(RepairOrderStatus::Estimated, $repairOrder->refresh()->status);
        $this->assertDatabaseHas('estimates', [
            'repair_order_id' => $repairOrder->id,
            'version' => 1,
        ]);
        $this->assertDatabaseHas('documents', [
            'type' => 'estimate_pdf',
            'documentable_type' => Estimate::class,
        ]);
    }

    public function test_draft_repair_order_without_lines_cannot_be_marked_estimated(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasErrors('status');

        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->refresh()->status);
    }

    public function test_estimated_repair_order_without_current_estimate_can_create_first_estimate_pdf(): void
    {
        Storage::fake('documents_local');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop, [
            'status' => RepairOrderStatus::Estimated,
        ]);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', __('repair_orders.estimate_created'));

        $this->assertSame(RepairOrderStatus::Estimated, $repairOrder->refresh()->status);
        $this->assertDatabaseHas('estimates', [
            'repair_order_id' => $repairOrder->id,
            'version' => 1,
        ]);
    }

    private function createMembership(
        User $user,
        Workshop $workshop,
        WorkshopUserRole $role = WorkshopUserRole::Owner,
    ): WorkshopUser {
        return WorkshopUser::create([
            'user_id' => $user->id,
            'workshop_id' => $workshop->id,
            'role' => $role,
        ]);
    }

    /**
     * @param  array{status?: RepairOrderStatus}  $overrides
     */
    private function createRepairOrder(Workshop $workshop, array $overrides = []): RepairOrder
    {
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Jane Driver',
            'phone' => '+1 555 123 4567',
            'normalized_phone' => '15551234567',
        ]);

        return RepairOrder::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'booking_request_id' => null,
            'status' => $overrides['status'] ?? RepairOrderStatus::Draft,
            'problem_description' => 'Brake noise on cold start.',
            'opened_at' => now(),
            'closed_at' => null,
        ]);
    }
}
