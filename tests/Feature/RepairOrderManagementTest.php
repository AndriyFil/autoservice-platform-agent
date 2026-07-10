<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\EstimateStatus;
use App\Enums\RepairOrderStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
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
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Jane Driver',
            'phone' => '+1 555 123 4567',
            'normalized_phone' => '15551234567',
        ]);
        $vehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'license_plate' => 'AA1234BB',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 555 123 4567',
            'problem_description' => 'Brake pedal feels soft.',
            'preferred_date' => '2026-06-20',
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
                ->where('defaults.customer_id', (string) $customer->id)
                ->where('defaults.vehicle_id', (string) $vehicle->id)
                ->where('defaults.booking_request_id', (string) $bookingRequest->id)
                ->where('defaults.requires_estimate_approval', true)
                ->where('defaults.problem_description', 'Brake pedal feels soft.')
                ->where('defaults.customer_phone', '+1 555 123 4567')
                ->where('sourceBookingRequest.id', $bookingRequest->id)
                ->where('sourceBookingRequest.customerName', 'Jane Driver')
                ->where('sourceBookingRequest.customerPhone', '+1 555 123 4567')
                ->where('sourceBookingRequest.originalMessage', 'Brake pedal feels soft.')
                ->where('sourceBookingRequest.problemDescription', 'Brake pedal feels soft.')
                ->where('sourceBookingRequest.existingCustomer.id', $customer->id)
                ->where('sourceBookingRequest.preferredDate', '2026-06-20'));
    }

    public function test_repair_order_create_page_prefills_problem_from_original_message_when_problem_description_is_blank(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => null,
            'customer_phone' => '+1 555 888 9999',
            'problem_description' => '',
            'original_message' => 'Opel Insignia, check engine light came on.',
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
                ->where('defaults.problem_description', 'Opel Insignia, check engine light came on.')
                ->where('sourceBookingRequest.originalMessage', 'Opel Insignia, check engine light came on.')
                ->where('sourceBookingRequest.problemDescription', ''));
    }

    public function test_repair_order_create_page_shows_new_customer_when_booking_phone_is_not_in_active_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        Customer::create([
            'workshop_id' => $otherWorkshop->id,
            'name' => 'Other Workshop Customer',
            'phone' => '+1 555 777 0000',
            'normalized_phone' => '15557770000',
        ]);
        $bookingRequest = $this->createBookingRequest($activeWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => null,
            'customer_phone' => '+1 555 777 0000',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Create')
                ->where('defaults.customer_id', '')
                ->where('defaults.vehicle_id', '')
                ->where('defaults.customer_name', '')
                ->where('defaults.customer_phone', '+1 555 777 0000')
                ->where('sourceBookingRequest.existingCustomer', null));
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

    public function test_confirmed_booking_request_reuses_existing_customer_by_phone_in_active_workshop(): void
    {
        Carbon::setTestNow('2026-06-12 10:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Jane Driver',
            'phone' => '+1 555 123 4567',
            'normalized_phone' => '15551234567',
        ]);
        $vehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'license_plate' => 'AA1234BB',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 555 123 4567',
            'problem_description' => 'Brake pedal feels soft.',
        ]);
        $usersBefore = User::query()->count();

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
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
        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($usersBefore, User::query()->count());
        $this->assertSame($workshop->id, $repairOrder->workshop_id);
        $this->assertSame($customer->id, $repairOrder->customer_id);
        $this->assertSame($vehicle->id, $repairOrder->vehicle_id);
        $this->assertSame($bookingRequest->id, $repairOrder->booking_request_id);
        $this->assertSame($user->id, $repairOrder->created_by_user_id);
        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->status);
        $this->assertTrue($repairOrder->requires_estimate_approval);
        $this->assertSame('Brake pedal feels soft.', $repairOrder->problem_description);
        $this->assertSame('2026-06-12 10:00:00', $repairOrder->opened_at->toDateTimeString());
        $this->assertNull($repairOrder->closed_at);
        $this->assertSame(BookingRequestStatus::Confirmed, $bookingRequest->status);
    }

    public function test_repair_order_model_casts_requires_estimate_approval_as_boolean(): void
    {
        $repairOrder = RepairOrder::factory()->create([
            'requires_estimate_approval' => 1,
        ]);

        $this->assertTrue($repairOrder->refresh()->requires_estimate_approval);
    }

    public function test_manual_repair_order_can_be_created_without_requiring_estimate_approval(): void
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

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $customer->id,
                'problem_description' => 'Oil change.',
                'requires_estimate_approval' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse(RepairOrder::query()->firstOrFail()->requires_estimate_approval);
    }

    public function test_repair_order_created_from_booking_request_can_disable_estimate_approval_requirement(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Brake noise.',
                'requires_estimate_approval' => false,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse(RepairOrder::query()->firstOrFail()->requires_estimate_approval);
    }

    public function test_workshop_level_approval_setting_is_not_schema_source_of_truth(): void
    {
        $this->assertFalse(Schema::hasColumn('workshops', 'require_estimate_approval'));
        $this->assertTrue(Schema::hasColumn('repair_orders', 'requires_estimate_approval'));
    }

    public function test_confirmed_booking_request_reuses_existing_customer_by_phone_normalized(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Jane Driver',
            'phone' => '+380685620040',
            'normalized_phone' => '380685620040',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => 'Jane Driver',
            'customer_phone' => '068 562 00 40',
            'problem_description' => 'Check engine light.',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Check engine light.',
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame('+380685620040', $customer->refresh()->phone_normalized);
        $this->assertSame('+380685620040', $bookingRequest->refresh()->customer_phone_normalized);
        $this->assertSame($customer->id, $repairOrder->customer_id);
    }

    public function test_confirmed_booking_request_creates_customer_if_phone_does_not_exist_in_active_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        Customer::create([
            'workshop_id' => $otherWorkshop->id,
            'name' => 'Other Workshop Customer',
            'phone' => '+1 555 123 4567',
            'normalized_phone' => '15551234567',
        ]);
        $bookingRequest = $this->createBookingRequest($activeWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => null,
            'customer_phone' => '+1 555 123 4567',
            'problem_description' => 'Check engine light.',
        ]);
        $usersBefore = User::query()->count();

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Check engine light.',
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();
        $customer = Customer::query()
            ->where('workshop_id', $activeWorkshop->id)
            ->where('phone_normalized', '+15551234567')
            ->firstOrFail();

        $this->assertDatabaseCount('customers', 2);
        $this->assertSame($usersBefore, User::query()->count());
        $this->assertNull($customer->name);
        $this->assertSame('+1 555 123 4567', $customer->phone);
        $this->assertSame('+15551234567', $customer->phone_normalized);
        $this->assertSame($customer->id, $repairOrder->customer_id);
        $this->assertNull($repairOrder->vehicle_id);
    }

    public function test_confirmed_booking_request_customer_name_is_optional_when_creating_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => null,
            'customer_phone' => '+1 555 555 1212',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'customer_name' => '',
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertSessionHasNoErrors();

        $customer = Customer::query()->where('phone_normalized', '+15555551212')->firstOrFail();
        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertNull($customer->name);
        $this->assertSame($customer->id, $repairOrder->customer_id);
    }

    public function test_confirmed_booking_request_does_not_overwrite_existing_customer_name(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Stored Customer Name',
            'phone' => '+1 555 333 1212',
            'normalized_phone' => '15553331212',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_name' => 'Submitted Different Name',
            'customer_phone' => '+1 555 333 1212',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'customer_name' => 'Posted Different Name',
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertSame($customer->id, $repairOrder->customer_id);
        $this->assertSame('Stored Customer Name', $customer->refresh()->name);
    }

    public function test_confirmed_booking_request_can_create_repair_order_with_null_vehicle(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_phone' => '+1 555 111 2222',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertNull($repairOrder->vehicle_id);
    }

    public function test_confirmed_booking_request_can_create_vehicle_from_make_model_and_year(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_phone' => '+1 555 222 3333',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $bookingRequest->problem_description,
                'new_vehicle' => [
                    'make' => 'Opel',
                    'model' => 'Insignia',
                    'year' => 2018,
                    'plate' => 'AA5555BB',
                ],
            ])
            ->assertSessionHasNoErrors();

        $vehicle = Vehicle::query()->firstOrFail();
        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertSame('Opel', $vehicle->brand);
        $this->assertSame('Insignia', $vehicle->model);
        $this->assertSame(2018, $vehicle->year);
        $this->assertSame('AA5555BB', $vehicle->license_plate);
        $this->assertSame($vehicle->id, $repairOrder->vehicle_id);
        $this->assertSame($repairOrder->customer_id, $vehicle->customer_id);
    }

    public function test_confirmed_booking_request_uses_selected_vehicle_when_new_vehicle_fields_are_also_posted(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Multi Vehicle Customer',
            'phone' => '+1 555 224 4668',
            'normalized_phone' => '15552244668',
        ]);
        $selectedVehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Honda',
            'model' => 'Civic',
            'year' => 2021,
            'license_plate' => 'SELECTED',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_phone' => '+1 555 224 4668',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'vehicle_id' => $selectedVehicle->id,
                'problem_description' => $bookingRequest->problem_description,
                'new_vehicle' => [
                    'make' => 'Posted',
                    'model' => 'Should Not Create',
                    'year' => 2020,
                    'plate' => 'POSTED',
                ],
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertDatabaseCount('vehicles', 1);
        $this->assertSame($selectedVehicle->id, $repairOrder->vehicle_id);
    }

    public function test_confirmed_booking_request_cannot_use_vehicle_from_another_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Booking Customer',
            'phone' => '+1 555 333 4444',
            'normalized_phone' => '15553334444',
        ]);
        $otherCustomer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Other Customer',
            'phone' => '+1 555 444 5555',
            'normalized_phone' => '15554445555',
        ]);
        $otherVehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $otherCustomer->id,
            'brand' => 'Ford',
            'model' => 'Focus',
            'year' => 2020,
            'license_plate' => 'OTHER',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_phone' => $customer->phone,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'vehicle_id' => $otherVehicle->id,
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertRedirect(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertSessionHasErrors('repair_order');

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_confirmed_booking_request_cannot_use_vehicle_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        Customer::create([
            'workshop_id' => $activeWorkshop->id,
            'name' => 'Booking Customer',
            'phone' => '+1 555 666 7777',
            'normalized_phone' => '15556667777',
        ]);
        $otherCustomer = Customer::create([
            'workshop_id' => $otherWorkshop->id,
            'name' => 'Other Customer',
            'phone' => '+1 555 777 8888',
            'normalized_phone' => '15557778888',
        ]);
        $otherVehicle = Vehicle::create([
            'workshop_id' => $otherWorkshop->id,
            'customer_id' => $otherCustomer->id,
            'brand' => 'Skoda',
            'model' => 'Octavia',
            'year' => 2019,
            'license_plate' => 'CROSS',
        ]);
        $bookingRequest = $this->createBookingRequest($activeWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'vehicle_id' => null,
            'customer_phone' => '+1 555 666 7777',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->from(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'vehicle_id' => $otherVehicle->id,
                'problem_description' => $bookingRequest->problem_description,
            ])
            ->assertRedirect(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertSessionHasErrors('repair_order');

        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_repair_order_from_booking_request_copies_booking_request_problem_description(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'problem_description' => 'Original safe customer problem.',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'customer_id' => $bookingRequest->customer_id,
                'vehicle_id' => $bookingRequest->vehicle_id,
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Posted replacement should not win.',
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertSame('Original safe customer problem.', $repairOrder->problem_description);
    }

    public function test_repair_order_from_booking_request_uses_original_message_when_problem_description_is_blank(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'problem_description' => '',
            'original_message' => 'Customer wrote the whole initial intake here.',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Posted fallback should not win.',
            ])
            ->assertSessionHasNoErrors();

        $repairOrder = RepairOrder::query()->firstOrFail();

        $this->assertSame('Customer wrote the whole initial intake here.', $repairOrder->problem_description);
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
        $customerCount = Customer::query()->count();
        $vehicleCount = Vehicle::query()->count();

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
        $this->assertSame($customerCount, Customer::query()->count());
        $this->assertSame($vehicleCount, Vehicle::query()->count());
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
                ->where('repairOrders.0.availableStatusTransitions.0.value', 'in_progress')
                ->where('repairOrders.0.availableStatusTransitions.0.label', 'Start work')
                ->where('repairOrders.0.availableStatusTransitions.1.value', 'cancelled')
                ->where('repairOrders.0.availableStatusTransitions.1.label', 'Cancel order')
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
                ->where('repairOrder.bookingRequest.preferredDate', '2026-06-20')
                ->where('repairOrder.statusActions.canMarkEstimated', false)
                ->where('repairOrder.statusActions.hasEstimate', false)
                ->where('repairOrder.availableStatusTransitions.0.value', 'in_progress')
                ->where('repairOrder.availableStatusTransitions.0.label', 'Start work')
                ->where('repairOrder.availableStatusTransitions.1.value', 'cancelled')
                ->where('repairOrder.availableStatusTransitions.1.label', 'Cancel order'));
    }

    public function test_repair_order_show_available_status_transitions_match_statuses(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $expectations = [
            RepairOrderStatus::Draft->value => [
                'status' => RepairOrderStatus::Draft,
                'canMarkEstimated' => true,
                'hasEstimate' => false,
                'availableStatusTransitions' => [
                    ['value' => 'in_progress', 'label' => 'Start work'],
                    ['value' => 'cancelled', 'label' => 'Cancel order'],
                ],
            ],
            RepairOrderStatus::Estimated->value => [
                'status' => RepairOrderStatus::Estimated,
                'canMarkEstimated' => true,
                'hasEstimate' => true,
                'availableStatusTransitions' => [
                    ['value' => 'in_progress', 'label' => 'Start work'],
                    ['value' => 'cancelled', 'label' => 'Cancel order'],
                ],
            ],
            RepairOrderStatus::InProgress->value => [
                'status' => RepairOrderStatus::InProgress,
                'canMarkEstimated' => true,
                'hasEstimate' => true,
                'availableStatusTransitions' => [
                    ['value' => 'completed', 'label' => 'Complete order'],
                    ['value' => 'cancelled', 'label' => 'Cancel order'],
                ],
            ],
            RepairOrderStatus::Completed->value => [
                'status' => RepairOrderStatus::Completed,
                'canMarkEstimated' => false,
                'hasEstimate' => true,
                'availableStatusTransitions' => [],
            ],
            RepairOrderStatus::Cancelled->value => [
                'status' => RepairOrderStatus::Cancelled,
                'canMarkEstimated' => false,
                'hasEstimate' => true,
                'availableStatusTransitions' => [],
            ],
        ];

        foreach ($expectations as $statusValue => $expectation) {
            $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
                'status' => BookingRequestStatus::Confirmed,
            ]), [
                'status' => $expectation['status'],
            ]);

            $repairOrder->lines()->create([
                'type' => 'labor',
                'description' => 'Inspection',
                'quantity' => 1,
                'unit_price_cents' => 10000,
                'tax_rate' => 20,
                'sort_order' => 1,
            ]);

            if ($expectation['hasEstimate']) {
                Estimate::factory()->create([
                    'repair_order_id' => $repairOrder->id,
                    'version' => 1,
                    'status' => EstimateStatus::Generated,
                    'generated_at' => Carbon::parse('2026-07-03 09:30:00'),
                ]);
            }

            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->get(route('dashboard.repair-orders.show', $repairOrder))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where('repairOrder.status.value', $statusValue)
                    ->where('repairOrder.statusActions.canMarkEstimated', $expectation['canMarkEstimated'])
                    ->where('repairOrder.statusActions.hasEstimate', $expectation['hasEstimate'])
                    ->where('repairOrder.availableStatusTransitions', $expectation['availableStatusTransitions']));
        }
    }

    public function test_repair_order_show_exposes_document_list_for_documents_tab(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop);
        $repairOrder = $this->createRepairOrder($bookingRequest);
        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'version' => 1,
            'status' => EstimateStatus::Generated,
            'generated_at' => Carbon::parse('2026-07-03 09:30:00'),
        ]);
        $document = $estimate->documents()->create([
            'workshop_id' => $workshop->id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => 'documents_local',
            'path' => 'workshops/'.$workshop->id.'/estimates/'.$estimate->id.'/estimate-v1.pdf',
            'filename' => 'estimate-v1.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'estimate-v1'),
            'generated_at' => Carbon::parse('2026-07-03 09:31:00'),
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Show')
                ->where('repairOrder.documents.0.id', $document->id)
                ->where('repairOrder.documents.0.filename', 'estimate-v1.pdf')
                ->where('repairOrder.documents.0.type.value', 'estimate_pdf')
                ->where('repairOrder.documents.0.status.value', 'generated')
                ->where('repairOrder.documents.0.generatedAt', '2026-07-03T09:31:00.000000Z')
                ->where('repairOrder.documents.0.downloadUrl', route('dashboard.documents.download', $document)));
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
                ->where('linkedRepairOrder', null)
                ->where('canCreateRepairOrder', true));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $withRepairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookingRequest.status.value', 'confirmed')
                ->where('linkedRepairOrder.id', $repairOrder->id)
                ->where('linkedRepairOrder.status.value', 'draft')
                ->where('canCreateRepairOrder', false));
    }

    public function test_estimated_repair_order_cannot_complete_directly(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Estimated,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                'status' => RepairOrderStatus::Completed->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasErrors('status');

        $this->assertSame(RepairOrderStatus::Estimated, $repairOrder->refresh()->status);
        $this->assertNull($repairOrder->closed_at);
    }

    public function test_draft_repair_order_cannot_be_marked_estimated_through_status_route(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Draft,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                'status' => RepairOrderStatus::Estimated->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasErrors('status');

        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->refresh()->status);
        $this->assertNull($repairOrder->closed_at);
    }

    public function test_draft_repair_order_can_start_work(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Draft,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                'status' => RepairOrderStatus::InProgress->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Repair order started.');

        $this->assertSame(RepairOrderStatus::InProgress, $repairOrder->refresh()->status);
        $this->assertNull($repairOrder->closed_at);
    }

    public function test_estimated_repair_order_can_start_work(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Estimated,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                'status' => RepairOrderStatus::InProgress->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Repair order started.');

        $this->assertSame(RepairOrderStatus::InProgress, $repairOrder->refresh()->status);
        $this->assertNull($repairOrder->closed_at);
    }

    public function test_in_progress_repair_order_can_be_completed(): void
    {
        Carbon::setTestNow('2026-06-12 11:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::InProgress,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                'status' => RepairOrderStatus::Completed->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Repair order completed.');

        $repairOrder->refresh();

        $this->assertSame(RepairOrderStatus::Completed, $repairOrder->status);
        $this->assertSame('2026-06-12 11:00:00', $repairOrder->closed_at->toDateTimeString());
    }

    public function test_estimated_repair_order_can_be_cancelled(): void
    {
        Carbon::setTestNow('2026-06-12 12:00:00');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder(
            $this->createBookingRequest($workshop, [
                'status' => BookingRequestStatus::Confirmed,
            ]),
            [
                'status' => RepairOrderStatus::Estimated,
            ],
        );

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                'status' => RepairOrderStatus::Cancelled->value,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Repair order cancelled.');

        $repairOrder->refresh();

        $this->assertSame(RepairOrderStatus::Cancelled, $repairOrder->status);
        $this->assertSame('2026-06-12 12:00:00', $repairOrder->closed_at->toDateTimeString());
    }

    public function test_completed_repair_order_cannot_mutate(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Completed,
            'closed_at' => Carbon::parse('2026-06-12 11:00:00'),
        ]);

        foreach ([RepairOrderStatus::Draft, RepairOrderStatus::InProgress, RepairOrderStatus::Completed, RepairOrderStatus::Cancelled] as $targetStatus) {
            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard.repair-orders.show', $repairOrder))
                ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                    'status' => $targetStatus->value,
                ])
                ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
                ->assertSessionHasErrors('status');
        }

        $repairOrder->refresh();

        $this->assertSame(RepairOrderStatus::Completed, $repairOrder->status);
        $this->assertSame('2026-06-12 11:00:00', $repairOrder->closed_at->toDateTimeString());
    }

    public function test_cancelled_repair_order_cannot_mutate(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Cancelled,
            'closed_at' => Carbon::parse('2026-06-12 12:00:00'),
        ]);

        foreach ([RepairOrderStatus::Draft, RepairOrderStatus::Estimated, RepairOrderStatus::InProgress, RepairOrderStatus::Completed] as $targetStatus) {
            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard.repair-orders.show', $repairOrder))
                ->patch(route('dashboard.repair-orders.status', $repairOrder), [
                    'status' => $targetStatus->value,
                ])
                ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
                ->assertSessionHasErrors('status');
        }

        $repairOrder->refresh();

        $this->assertSame(RepairOrderStatus::Cancelled, $repairOrder->status);
        $this->assertSame('2026-06-12 12:00:00', $repairOrder->closed_at->toDateTimeString());
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
            ->patch(route('dashboard.repair-orders.status', $otherRepairOrder), [
                'status' => RepairOrderStatus::InProgress->value,
            ])
            ->assertNotFound();

        $this->assertSame(RepairOrderStatus::Draft, $otherRepairOrder->refresh()->status);
    }

    public function test_repair_order_show_exposes_estimate_approval_requirement_settings(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'requires_estimate_approval' => false,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('repairOrder.requiresEstimateApproval', false)
                ->where('repairOrder.canUpdateEstimateApprovalRequirement', true));
    }

    public function test_open_repair_order_estimate_approval_requirement_can_be_toggled(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop, WorkshopUserRole::Staff);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'requires_estimate_approval' => true,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.estimate-approval-requirement.update', $repairOrder), [
                'requires_estimate_approval' => false,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', 'Estimate approval requirement updated.');

        $this->assertFalse($repairOrder->refresh()->requires_estimate_approval);
    }

    public function test_completed_repair_order_estimate_approval_requirement_cannot_be_toggled(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'status' => RepairOrderStatus::Completed,
            'requires_estimate_approval' => true,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->patch(route('dashboard.repair-orders.estimate-approval-requirement.update', $repairOrder), [
                'requires_estimate_approval' => false,
            ])
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasErrors('requires_estimate_approval');

        $this->assertTrue($repairOrder->refresh()->requires_estimate_approval);
    }

    public function test_cross_workshop_estimate_approval_requirement_update_is_not_accessible(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherRepairOrder = $this->createRepairOrder($this->createBookingRequest($otherWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]), [
            'requires_estimate_approval' => true,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('dashboard.repair-orders.estimate-approval-requirement.update', $otherRepairOrder), [
                'requires_estimate_approval' => false,
            ])
            ->assertNotFound();

        $this->assertTrue($otherRepairOrder->refresh()->requires_estimate_approval);
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
     *     customer_id?: int|null,
     *     vehicle_id?: int|null,
     *     problem_description?: string,
     *     original_message?: string|null,
     *     preferred_date?: string|null,
     *     status?: BookingRequestStatus,
     *     vehicle?: array{brand?: string|null, model?: string|null, year?: int|null, license_plate?: string|null}
     * }  $overrides
     */
    private function createBookingRequest(Workshop $workshop, array $overrides = []): BookingRequest
    {
        $customerNumber = BookingRequest::query()->count() + 1;
        $customerName = array_key_exists('customer_name', $overrides) ? $overrides['customer_name'] : 'Jane Driver';
        $customerPhone = array_key_exists('customer_phone', $overrides) ? $overrides['customer_phone'] : "+1 (555) 123-45{$customerNumber}";
        $customer = null;

        if (! array_key_exists('customer_id', $overrides)) {
            $customer = Customer::create([
                'workshop_id' => $workshop->id,
                'name' => $customerName,
                'phone' => $customerPhone,
                'normalized_phone' => "155512345{$customerNumber}",
            ]);
        }

        $vehicle = null;

        if (isset($overrides['vehicle'])) {
            $vehicle = Vehicle::create([
                'workshop_id' => $workshop->id,
                'customer_id' => $customer?->id ?? $overrides['customer_id'],
                'brand' => $overrides['vehicle']['brand'] ?? null,
                'model' => $overrides['vehicle']['model'] ?? null,
                'year' => $overrides['vehicle']['year'] ?? null,
                'license_plate' => $overrides['vehicle']['license_plate'] ?? null,
            ]);
        }

        $problemDescription = $overrides['problem_description'] ?? 'Brake noise on cold start.';

        return BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $overrides['customer_id'] ?? $customer?->id,
            'vehicle_id' => $overrides['vehicle_id'] ?? $vehicle?->id,
            'created_by_user_id' => null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'problem_description' => $problemDescription,
            'original_message' => $overrides['original_message'] ?? ($problemDescription !== '' ? $problemDescription : null),
            'preferred_date' => $overrides['preferred_date'] ?? null,
            'status' => $overrides['status'] ?? BookingRequestStatus::New,
        ]);
    }

    /**
     * @param  array{status?: RepairOrderStatus, requires_estimate_approval?: bool, opened_at?: Carbon, closed_at?: Carbon|null}  $overrides
     */
    private function createRepairOrder(BookingRequest $bookingRequest, array $overrides = []): RepairOrder
    {
        return RepairOrder::create([
            'workshop_id' => $bookingRequest->workshop_id,
            'customer_id' => $bookingRequest->customer_id,
            'vehicle_id' => $bookingRequest->vehicle_id,
            'booking_request_id' => $bookingRequest->id,
            'status' => $overrides['status'] ?? RepairOrderStatus::Draft,
            'requires_estimate_approval' => $overrides['requires_estimate_approval'] ?? true,
            'problem_description' => $bookingRequest->problem_description,
            'opened_at' => $overrides['opened_at'] ?? Carbon::parse('2026-06-12 10:00:00'),
            'closed_at' => $overrides['closed_at'] ?? null,
        ]);
    }
}
