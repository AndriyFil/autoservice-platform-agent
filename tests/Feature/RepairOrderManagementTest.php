<?php

namespace Tests\Feature;

use App\Enums\BookingRequestStatus;
use App\Enums\RepairOrderStatus;
use App\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepairOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_confirming_booking_request_redirects_to_repair_order_create_form_without_creating_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::New,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                'status' => BookingRequestStatus::Confirmed->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ]));

        $this->assertSame(BookingRequestStatus::Confirmed, $bookingRequest->refresh()->status);
        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_confirming_booking_request_with_existing_repair_order_redirects_to_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::New,
        ]);
        $repairOrder = $this->createRepairOrder($bookingRequest);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                'status' => BookingRequestStatus::Confirmed->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $this->assertSame(BookingRequestStatus::Confirmed, $bookingRequest->refresh()->status);
        $this->assertDatabaseCount('repair_orders', 1);
    }

    public function test_repair_order_create_page_is_prefilled_from_booking_request(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'problem_description' => 'Brake pedal feels soft.',
            'preferred_date' => '2026-06-20',
            'vehicle' => [
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'AA1234BB',
            ],
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Create')
                ->where('defaults.customer_id', (string) $bookingRequest->customer_id)
                ->where('defaults.vehicle_id', (string) $bookingRequest->vehicle_id)
                ->where('defaults.booking_request_id', (string) $bookingRequest->id)
                ->where('defaults.problem_description', 'Brake pedal feels soft.')
                ->where('sourceBookingRequest.id', $bookingRequest->id)
                ->where('sourceBookingRequest.customerName', 'Jane Driver')
                ->where('sourceBookingRequest.preferredDate', '2026-06-20'));
    }

    public function test_cross_workshop_booking_request_cannot_prefill_repair_order_create_page(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $bookingRequest = $this->createBookingRequest($otherWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ]))
            ->assertNotFound();
    }

    public function test_repair_order_create_page_redirects_when_booking_request_already_has_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $repairOrder = $this->createRepairOrder($bookingRequest);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ]))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));
    }

    public function test_confirmed_booking_request_can_create_repair_order_from_form_submit(): void
    {
        Carbon::setTestNow('2026-06-12 10:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'problem_description' => 'Brake pedal feels soft.',
            'vehicle' => [
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'AA1234BB',
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $bookingRequest->customer_id,
                'vehicle_id' => $bookingRequest->vehicle_id,
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Brake pedal feels soft.',
            ]);

        $repairOrder = RepairOrder::query()->first();

        $this->assertNotNull($repairOrder);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $bookingRequest->refresh();

        $this->assertDatabaseCount('repair_orders', 1);
        $this->assertSame($workshop->id, $repairOrder->workshop_id);
        $this->assertSame($bookingRequest->customer_id, $repairOrder->customer_id);
        $this->assertSame($bookingRequest->vehicle_id, $repairOrder->vehicle_id);
        $this->assertSame($bookingRequest->id, $repairOrder->booking_request_id);
        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->status);
        $this->assertSame('Brake pedal feels soft.', $repairOrder->problem_description);
        $this->assertSame('2026-06-12 10:00:00', $repairOrder->opened_at->toDateTimeString());
        $this->assertNull($repairOrder->closed_at);
        $this->assertSame(BookingRequestStatus::Confirmed, $bookingRequest->status);
    }

    public function test_manual_repair_order_can_be_created_without_booking_request(): void
    {
        Carbon::setTestNow('2026-06-12 09:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Walk In',
            'phone' => '+1 555 111 2222',
            'normalized_phone' => '15551112222',
        ]);
        $vehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'license_plate' => 'AB1234CD',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'problem_description' => 'Walk-in oil leak inspection.',
            ]);

        $repairOrder = RepairOrder::query()->first();

        $this->assertNotNull($repairOrder);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $this->assertDatabaseCount('repair_orders', 1);
        $this->assertSame($workshop->id, $repairOrder->workshop_id);
        $this->assertSame($customer->id, $repairOrder->customer_id);
        $this->assertSame($vehicle->id, $repairOrder->vehicle_id);
        $this->assertNull($repairOrder->booking_request_id);
        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->status);
        $this->assertSame('Walk-in oil leak inspection.', $repairOrder->problem_description);
        $this->assertSame('2026-06-12 09:00:00', $repairOrder->opened_at->toDateTimeString());
        $this->assertNull($repairOrder->closed_at);
    }

    public function test_manual_repair_order_can_be_created_without_vehicle(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Phone Customer',
            'phone' => '+1 555 333 4444',
            'normalized_phone' => '15553334444',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $customer->id,
                'vehicle_id' => null,
                'problem_description' => 'Customer will provide vehicle details on arrival.',
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertSame($customer->id, $repairOrder->customer_id);
        $this->assertNull($repairOrder->vehicle_id);
        $this->assertNull($repairOrder->booking_request_id);
    }

    public function test_manual_repair_order_rejects_customer_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherCustomer = Customer::create([
            'workshop_id' => $otherWorkshop->id,
            'name' => 'Other Customer',
            'phone' => '+1 555 555 0000',
            'normalized_phone' => '15555550000',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $otherCustomer->id,
                'vehicle_id' => null,
                'problem_description' => 'Should not cross workshops.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_manual_repair_order_rejects_vehicle_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $customer = Customer::create([
            'workshop_id' => $activeWorkshop->id,
            'name' => 'Active Customer',
            'phone' => '+1 555 555 1111',
            'normalized_phone' => '15555551111',
        ]);
        $otherVehicle = Vehicle::create([
            'workshop_id' => $otherWorkshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Ford',
            'model' => 'Focus',
            'license_plate' => 'OTHER',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->from(route('dashboard.repair-orders.create'))
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $customer->id,
                'vehicle_id' => $otherVehicle->id,
                'problem_description' => 'Should reject vehicle from another workshop.',
            ])
            ->assertRedirect(route('dashboard.repair-orders.create'))
            ->assertSessionHasErrors('repair_order');

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_manual_repair_order_rejects_vehicle_from_another_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $selectedCustomer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Selected Customer',
            'phone' => '+1 555 555 2222',
            'normalized_phone' => '15555552222',
        ]);
        $vehicleOwner = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Vehicle Owner',
            'phone' => '+1 555 555 3333',
            'normalized_phone' => '15555553333',
        ]);
        $vehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $vehicleOwner->id,
            'brand' => 'Nissan',
            'model' => 'Leaf',
            'license_plate' => 'OWNER',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.create'))
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $selectedCustomer->id,
                'vehicle_id' => $vehicle->id,
                'problem_description' => 'Should reject vehicle from another customer.',
            ])
            ->assertRedirect(route('dashboard.repair-orders.create'))
            ->assertSessionHasErrors('repair_order');

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_unconfirmed_booking_request_cannot_create_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::New,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.booking-requests.show', $bookingRequest))
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $bookingRequest->customer_id,
                'vehicle_id' => $bookingRequest->vehicle_id,
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertSessionHasErrors('repair_order');

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_cross_workshop_booking_request_cannot_create_repair_order(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $bookingRequest = $this->createBookingRequest($otherWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $bookingRequest->customer_id,
                'vehicle_id' => $bookingRequest->vehicle_id,
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_booking_request_can_have_only_one_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $this->createRepairOrder($bookingRequest);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.booking-requests.show', $bookingRequest))
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $bookingRequest->customer_id,
                'vehicle_id' => $bookingRequest->vehicle_id,
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertSessionHasErrors('repair_order');

        $this->assertDatabaseCount('repair_orders', 1);
    }

    public function test_repair_order_index_lists_only_active_workshop_repair_orders(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);

        $activeBookingRequest = $this->createBookingRequest($activeWorkshop, [
            'customer_name' => 'Active Customer',
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $otherBookingRequest = $this->createBookingRequest($otherWorkshop, [
            'customer_name' => 'Other Customer',
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $activeRepairOrder = $this->createRepairOrder($activeBookingRequest);
        $manualRepairOrder = RepairOrder::create([
            'workshop_id' => $activeWorkshop->id,
            'customer_id' => $activeBookingRequest->customer_id,
            'vehicle_id' => null,
            'booking_request_id' => null,
            'status' => RepairOrderStatus::Draft,
            'problem_description' => 'Manual phone-call work.',
            'opened_at' => Carbon::parse('2026-06-12 11:00:00'),
            'closed_at' => null,
        ]);
        $this->createRepairOrder($otherBookingRequest);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('dashboard.repair-orders.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Index')
                ->where('activeWorkshop.id', $activeWorkshop->id)
                ->where('activeWorkshop.name', 'Main Auto')
                ->has('repairOrders', 2)
                ->where('repairOrders.0.id', $manualRepairOrder->id)
                ->where('repairOrders.0.customerName', 'Active Customer')
                ->where('repairOrders.0.status.value', 'draft')
                ->where('repairOrders.1.id', $activeRepairOrder->id));
    }

    public function test_repair_order_show_is_scoped_to_active_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherRepairOrder = $this->createRepairOrder($this->createBookingRequest($otherWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('dashboard.repair-orders.show', $otherRepairOrder))
            ->assertNotFound();
    }

    public function test_repair_order_show_exposes_action_availability_data(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 555 123 4567',
            'status' => BookingRequestStatus::Confirmed,
            'preferred_date' => '2026-06-20',
            'vehicle' => [
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'AA1234BB',
            ],
        ]);
        $repairOrder = $this->createRepairOrder($bookingRequest);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Show')
                ->where('repairOrder.id', $repairOrder->id)
                ->where('repairOrder.status.value', 'draft')
                ->where('repairOrder.customer.name', 'Jane Driver')
                ->where('repairOrder.customer.phone', '+1 555 123 4567')
                ->where('repairOrder.vehicle.brand', 'Honda')
                ->where('repairOrder.bookingRequest.id', $bookingRequest->id)
                ->where('repairOrder.bookingRequest.status.value', 'confirmed')
                ->where('repairOrder.bookingRequest.preferredDate', '2026-06-20'));
    }

    public function test_manual_repair_order_show_exposes_nullable_booking_request(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Manual Customer',
            'phone' => '+1 555 999 0000',
            'normalized_phone' => '15559990000',
        ]);
        $repairOrder = RepairOrder::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'booking_request_id' => null,
            'status' => RepairOrderStatus::Draft,
            'problem_description' => 'Manual intake.',
            'opened_at' => Carbon::parse('2026-06-12 10:00:00'),
            'closed_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Show')
                ->where('repairOrder.id', $repairOrder->id)
                ->where('repairOrder.bookingRequest', null)
                ->where('repairOrder.vehicle', null));
    }

    public function test_booking_request_details_exposes_repair_order_link_state(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $withoutRepairOrder = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $withRepairOrder = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $repairOrder = $this->createRepairOrder($withRepairOrder);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $withoutRepairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookingRequest.status.value', 'confirmed')
                ->where('bookingRequest.repairOrder', null));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $withRepairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookingRequest.status.value', 'confirmed')
                ->where('bookingRequest.repairOrder.id', $repairOrder->id)
                ->where('bookingRequest.repairOrder.status.value', 'draft'));
    }

    public function test_open_repair_order_can_be_completed(): void
    {
        Carbon::setTestNow('2026-06-12 11:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.complete', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Repair order completed.');

        $repairOrder->refresh();

        $this->assertSame(RepairOrderStatus::Completed, $repairOrder->status);
        $this->assertSame('2026-06-12 11:00:00', $repairOrder->closed_at->toDateTimeString());
    }

    public function test_open_repair_order_can_be_cancelled(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.cancel', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Repair order cancelled.');

        $repairOrder->refresh();

        $this->assertSame(RepairOrderStatus::Cancelled, $repairOrder->status);
        $this->assertSame('2026-06-12 12:00:00', $repairOrder->closed_at->toDateTimeString());
    }

    public function test_closed_repair_order_cannot_be_completed_or_cancelled_again(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $completedRepairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Completed,
            'closed_at' => Carbon::parse('2026-06-12 11:00:00'),
        ]);
        $cancelledRepairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Cancelled,
            'closed_at' => Carbon::parse('2026-06-12 12:00:00'),
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $completedRepairOrder))
            ->post(route('dashboard.repair-orders.complete', $completedRepairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $completedRepairOrder))
            ->assertSessionHasErrors('status');

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $cancelledRepairOrder))
            ->post(route('dashboard.repair-orders.cancel', $cancelledRepairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $cancelledRepairOrder))
            ->assertSessionHasErrors('status');

        $this->assertSame(RepairOrderStatus::Completed, $completedRepairOrder->refresh()->status);
        $this->assertSame(RepairOrderStatus::Cancelled, $cancelledRepairOrder->refresh()->status);
    }

    public function test_cross_workshop_repair_order_status_actions_are_not_accessible(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherRepairOrder = $this->createRepairOrder($this->createBookingRequest($otherWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.complete', $otherRepairOrder))
            ->assertNotFound();

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.cancel', $otherRepairOrder))
            ->assertNotFound();

        $this->assertSame(RepairOrderStatus::Draft, $otherRepairOrder->refresh()->status);
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
     * @param  array{
     *     customer_name?: string,
     *     customer_phone?: string,
     *     problem_description?: string,
     *     preferred_date?: string|null,
     *     status?: BookingRequestStatus,
     *     vehicle?: array{brand?: string|null, model?: string|null, license_plate?: string|null}
     * }  $overrides
     */
    private function createBookingRequest(Workshop $workshop, array $overrides = []): BookingRequest
    {
        $customerNumber = BookingRequest::query()->count() + 1;
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => $overrides['customer_name'] ?? 'Jane Driver',
            'phone' => $overrides['customer_phone'] ?? "+1 (555) 123-45{$customerNumber}",
            'normalized_phone' => "155512345{$customerNumber}",
        ]);

        $vehicle = null;

        if (isset($overrides['vehicle'])) {
            $vehicle = Vehicle::create([
                'workshop_id' => $workshop->id,
                'customer_id' => $customer->id,
                'brand' => $overrides['vehicle']['brand'] ?? null,
                'model' => $overrides['vehicle']['model'] ?? null,
                'license_plate' => $overrides['vehicle']['license_plate'] ?? null,
            ]);
        }

        return BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle?->id,
            'created_by_user_id' => null,
            'customer_name' => $overrides['customer_name'] ?? 'Jane Driver',
            'customer_phone' => $overrides['customer_phone'] ?? "+1 (555) 123-45{$customerNumber}",
            'problem_description' => $overrides['problem_description'] ?? 'Brake noise on cold start.',
            'preferred_date' => $overrides['preferred_date'] ?? null,
            'status' => $overrides['status'] ?? BookingRequestStatus::New,
        ]);
    }

    /**
     * @param  array{status?: RepairOrderStatus, opened_at?: Carbon, closed_at?: Carbon|null}  $overrides
     */
    private function createRepairOrder(BookingRequest $bookingRequest, array $overrides = []): RepairOrder
    {
        return RepairOrder::create([
            'workshop_id' => $bookingRequest->workshop_id,
            'customer_id' => $bookingRequest->customer_id,
            'vehicle_id' => $bookingRequest->vehicle_id,
            'booking_request_id' => $bookingRequest->id,
            'status' => $overrides['status'] ?? RepairOrderStatus::Draft,
            'problem_description' => $bookingRequest->problem_description,
            'opened_at' => $overrides['opened_at'] ?? Carbon::parse('2026-06-12 10:00:00'),
            'closed_at' => $overrides['closed_at'] ?? null,
        ]);
    }
}
