<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\RepairOrderStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardBookingRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_booking_request_details(): void
    {
        $bookingRequest = $this->createBookingRequest(Workshop::factory()->create());

        $this
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertRedirect('/login');
    }

    public function test_user_without_workshop_membership_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();
        $bookingRequest = $this->createBookingRequest(Workshop::factory()->create());

        $this
            ->actingAs($user)
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertRedirect('/workshop-onboarding');

        $this
            ->actingAs($user)
            ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                'status' => BookingRequestStatus::Confirmed->value,
            ])
            ->assertRedirect('/workshop-onboarding');
    }

    public function test_active_workshop_member_can_view_booking_request_details(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $this->createMembership($user, $workshop);

        $bookingRequest = $this->createBookingRequest($workshop, [
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 555 123 4567',
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
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/BookingRequests/Show')
                ->where('activeWorkshop.id', $workshop->id)
                ->where('bookingRequest.id', $bookingRequest->id)
                ->where('bookingRequest.customerName', 'Jane Driver')
                ->where('bookingRequest.customerPhone', '+1 555 123 4567')
                ->where('bookingRequest.preferredDate', '2026-06-20')
                ->where('bookingRequest.status.value', 'new')
                ->where('bookingRequest.vehicle.brand', 'Honda')
                ->where('bookingRequest.vehicle.model', 'Civic')
                ->where('bookingRequest.vehicle.licensePlate', 'AA1234BB')
                ->where('canCreateRepairOrder', true));
    }

    public function test_staff_can_view_booking_request_details(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop, WorkshopUserRole::Staff);

        $bookingRequest = $this->createBookingRequest($workshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk();
    }

    public function test_cross_workshop_booking_request_details_are_not_accessible(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);

        $otherBookingRequest = $this->createBookingRequest($otherWorkshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('dashboard.booking-requests.show', $otherBookingRequest))
            ->assertNotFound();
    }

    public function test_booking_request_show_identifies_matched_customer_by_normalized_phone(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Matched Driver',
            'phone' => '+380 67 111 22 33',
            'normalized_phone' => '380671112233',
        ]);
        $vehicle = Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Opel',
            'model' => 'Insignia',
            'year' => 2017,
            'license_plate' => 'AA1234BB',
        ]);
        $bookingRequest = BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => 'Jane from intake',
            'customer_phone' => '067 111 22 33',
            'problem_description' => 'Check engine light came on.',
            'original_message' => 'Opel Insignia, check engine light came on, maybe sensors, when can I come?',
            'preferred_date' => '2026-07-12',
            'status' => BookingRequestStatus::New,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('matchedCustomer.id', $customer->id)
                ->where('matchedCustomer.name', 'Matched Driver')
                ->where('matchedCustomer.showUrl', route('customers.show', $customer))
                ->where('matchedCustomerVehicles.0.id', $vehicle->id)
                ->where('matchedCustomerVehicles.0.brand', 'Opel')
                ->where('matchedCustomerVehicles.0.model', 'Insignia'));
    }

    public function test_booking_request_show_does_not_match_customer_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        Customer::create([
            'workshop_id' => $otherWorkshop->id,
            'name' => 'Other Workshop Customer',
            'phone' => '067 222 33 44',
            'normalized_phone' => '380672223344',
        ]);
        $bookingRequest = BookingRequest::create([
            'workshop_id' => $activeWorkshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => '067 222 33 44',
            'problem_description' => 'Customer hears a grinding noise.',
            'original_message' => 'Grinding noise after braking.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::New,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('matchedCustomer', null)
                ->where('matchedCustomerVehicles', [])
                ->where('customerCreationNotice', 'No existing customer found. A new customer will be created when a repair order is created.'));
    }

    public function test_booking_request_show_includes_original_message_and_extracted_data(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => 'Olena Driver',
            'customer_phone' => '+380 50 333 44 55',
            'problem_description' => 'Check engine light and sensor concern.',
            'original_message' => 'Opel Insignia, check engine light came on, maybe sensors, Friday morning?',
            'preferred_date' => '2026-07-17',
            'status' => BookingRequestStatus::New,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('bookingRequest.originalMessage', 'Opel Insignia, check engine light came on, maybe sensors, Friday morning?')
                ->where('bookingRequest.extractedData.customerName', 'Olena Driver')
                ->where('bookingRequest.extractedData.phone', '+380 50 333 44 55')
                ->where('bookingRequest.extractedData.preferredDate', '2026-07-17')
                ->where('bookingRequest.extractedData.summary', 'Check engine light and sensor concern.'));
    }

    public function test_booking_request_show_exposes_create_repair_order_when_no_linked_order_exists(): void
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
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('linkedRepairOrder', null)
                ->where('canCreateRepairOrder', true));
    }

    public function test_booking_request_show_exposes_linked_repair_order_instead_of_create_action(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $repairOrder = RepairOrder::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $bookingRequest->customer_id,
            'vehicle_id' => $bookingRequest->vehicle_id,
            'booking_request_id' => $bookingRequest->id,
            'status' => RepairOrderStatus::Draft,
            'problem_description' => $bookingRequest->problem_description,
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('linkedRepairOrder.id', $repairOrder->id)
                ->where('linkedRepairOrder.showUrl', route('dashboard.repair-orders.show', $repairOrder))
                ->where('canCreateRepairOrder', false));
    }

    public function test_duplicate_repair_order_creation_from_same_booking_request_is_prevented(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        RepairOrder::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $bookingRequest->customer_id,
            'vehicle_id' => $bookingRequest->vehicle_id,
            'booking_request_id' => $bookingRequest->id,
            'status' => RepairOrderStatus::Draft,
            'problem_description' => $bookingRequest->problem_description,
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $bookingRequest->problem_description,
                'requires_estimate_approval' => true,
            ])
            ->assertRedirect(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertSessionHasErrors('repair_order');

        $this->assertSame(1, RepairOrder::query()->where('booking_request_id', $bookingRequest->id)->count());
    }

    public function test_owner_can_apply_valid_status_transitions(): void
    {
        $this->assertRoleCanApplyValidStatusTransitions(WorkshopUserRole::Owner);
    }

    public function test_staff_can_apply_valid_status_transitions(): void
    {
        $this->assertRoleCanApplyValidStatusTransitions(WorkshopUserRole::Staff);
    }

    public function test_dashboard_list_exposes_booking_request_statuses_for_quick_actions(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $newRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::New,
        ]);
        $confirmedRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $rejectedRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Rejected,
        ]);
        $cancelledRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Cancelled,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('bookingRequests.0.id', $cancelledRequest->id)
                ->where('bookingRequests.0.status.value', 'cancelled')
                ->where('bookingRequests.1.id', $rejectedRequest->id)
                ->where('bookingRequests.1.status.value', 'rejected')
                ->where('bookingRequests.2.id', $confirmedRequest->id)
                ->where('bookingRequests.2.status.value', 'confirmed')
                ->where('bookingRequests.3.id', $newRequest->id)
                ->where('bookingRequests.3.status.value', 'new'));
    }

    public function test_dashboard_list_exposes_repair_order_link_state(): void
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
        $repairOrder = RepairOrder::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $withRepairOrder->customer_id,
            'vehicle_id' => $withRepairOrder->vehicle_id,
            'booking_request_id' => $withRepairOrder->id,
            'status' => RepairOrderStatus::Draft,
            'problem_description' => $withRepairOrder->problem_description,
            'opened_at' => now(),
            'closed_at' => null,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('bookingRequests.0.id', $withRepairOrder->id)
                ->where('bookingRequests.0.repairOrder.id', $repairOrder->id)
                ->where('bookingRequests.0.repairOrder.status.value', 'draft')
                ->where('bookingRequests.1.id', $withoutRepairOrder->id)
                ->where('bookingRequests.1.repairOrder', null));
    }

    public function test_dashboard_list_status_actions_redirect_back_to_dashboard(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $transitions = [
            [BookingRequestStatus::New, BookingRequestStatus::Confirmed],
            [BookingRequestStatus::New, BookingRequestStatus::Rejected],
            [BookingRequestStatus::New, BookingRequestStatus::Cancelled],
            [BookingRequestStatus::Confirmed, BookingRequestStatus::Cancelled],
        ];

        foreach ($transitions as [$fromStatus, $expectedStatus]) {
            $bookingRequest = $this->createBookingRequest($workshop, [
                'status' => $fromStatus,
            ]);

            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard'))
                ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                    'status' => $expectedStatus->value,
                ])
                ->assertRedirect(
                    $expectedStatus === BookingRequestStatus::Confirmed
                        ? route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id])
                        : route('dashboard')
                )
                ->assertSessionHas(
                    'status',
                    $expectedStatus === BookingRequestStatus::Confirmed
                        ? 'Booking request confirmed. Complete the repair order to start work.'
                        : 'Booking request status updated.'
                );

            $this->assertSame($expectedStatus, $bookingRequest->refresh()->status);
        }
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $transitions = [
            [BookingRequestStatus::Confirmed, BookingRequestStatus::Rejected, BookingRequestStatus::Confirmed],
            [BookingRequestStatus::Rejected, BookingRequestStatus::Confirmed, BookingRequestStatus::Rejected],
            [BookingRequestStatus::Rejected, BookingRequestStatus::Cancelled, BookingRequestStatus::Rejected],
            [BookingRequestStatus::Cancelled, BookingRequestStatus::Confirmed, BookingRequestStatus::Cancelled],
            [BookingRequestStatus::Cancelled, BookingRequestStatus::Rejected, BookingRequestStatus::Cancelled],
        ];

        foreach ($transitions as [$fromStatus, $targetStatus, $expectedStatus]) {
            $bookingRequest = $this->createBookingRequest($workshop, [
                'status' => $fromStatus,
            ]);

            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard.booking-requests.show', $bookingRequest))
                ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                    'status' => $targetStatus->value,
                ])
                ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest))
                ->assertSessionHasErrors('status');

            $this->assertSame($expectedStatus, $bookingRequest->refresh()->status);
        }
    }

    public function test_cross_workshop_status_action_is_not_accessible(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);

        $otherBookingRequest = $this->createBookingRequest($otherWorkshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('dashboard.booking-requests.status', $otherBookingRequest), [
                'status' => BookingRequestStatus::Confirmed->value,
            ])
            ->assertNotFound();

        $this->assertSame(BookingRequestStatus::New, $otherBookingRequest->refresh()->status);
    }

    private function assertRoleCanApplyValidStatusTransitions(WorkshopUserRole $role): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop, $role);

        $transitions = [
            [BookingRequestStatus::New, BookingRequestStatus::Confirmed],
            [BookingRequestStatus::New, BookingRequestStatus::Rejected],
            [BookingRequestStatus::New, BookingRequestStatus::Cancelled],
            [BookingRequestStatus::Confirmed, BookingRequestStatus::Cancelled],
        ];

        foreach ($transitions as [$fromStatus, $expectedStatus]) {
            $bookingRequest = $this->createBookingRequest($workshop, [
                'status' => $fromStatus,
            ]);

            $behavior = $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard.booking-requests.show', $bookingRequest))
                ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                    'status' => $expectedStatus->value,
                ]);
            if ($expectedStatus !== BookingRequestStatus::Confirmed) {
                $behavior->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest));
            } else {
                $behavior->assertRedirect(route('dashboard.repair-orders.create', [
                    'booking_request' => $bookingRequest->id,
                ]));
            }

            $this->assertSame($expectedStatus, $bookingRequest->refresh()->status);
        }
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
     *     original_message?: string|null,
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
            'original_message' => $overrides['original_message'] ?? null,
            'preferred_date' => $overrides['preferred_date'] ?? null,
            'status' => $overrides['status'] ?? BookingRequestStatus::New,
        ]);
    }
}
