<?php

namespace Tests\Feature;

use App\Enums\BookingRequestStatus;
use App\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\Customer;
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

    public function test_guest_is_redirected_from_dashboard_booking_request_create_flow(): void
    {
        $this
            ->get(route('booking-requests.create'))
            ->assertRedirect('/login');

        $this
            ->post(route('booking-requests.store'), $this->validCreatePayload())
            ->assertRedirect('/login');

        $this
            ->get(route('booking-requests.customers.search', ['q' => 'Jane']))
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

        $this
            ->actingAs($user)
            ->get(route('booking-requests.create'))
            ->assertRedirect('/workshop-onboarding');

        $this
            ->actingAs($user)
            ->post(route('booking-requests.store'), $this->validCreatePayload())
            ->assertRedirect('/workshop-onboarding');

        $this
            ->actingAs($user)
            ->get(route('booking-requests.customers.search', ['q' => 'Jane']))
            ->assertRedirect('/workshop-onboarding');
    }

    public function test_active_workshop_member_can_open_booking_request_create_page(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $this->createMembership($user, $workshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('booking-requests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/BookingRequests/Create')
                ->where('activeWorkshop.id', $workshop->id)
                ->where('activeWorkshop.name', 'Main Auto')
                ->where('activeWorkshop.slug', 'main-auto'));
    }

    public function test_customer_search_requires_minimum_query_length(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->getJson(route('booking-requests.customers.search', ['q' => 'J']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_customer_search_returns_only_active_workshop_customers_by_name(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);

        $activeCustomer = $this->createCustomer($activeWorkshop, [
            'name' => 'Jane Driver',
            'phone' => '+1 555 111 2222',
            'normalized_phone' => '15551112222',
        ]);
        $this->createCustomer($otherWorkshop, [
            'name' => 'Jane Other',
            'phone' => '+1 555 333 4444',
            'normalized_phone' => '15553334444',
        ]);
        $this->createCustomer($activeWorkshop, [
            'name' => 'Alex Rider',
            'phone' => '+1 555 555 6666',
            'normalized_phone' => '15555556666',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->getJson(route('booking-requests.customers.search', ['q' => 'jane']))
            ->assertOk()
            ->assertExactJson([
                [
                    'id' => $activeCustomer->id,
                    'name' => 'Jane Driver',
                    'phone' => '+1 555 111 2222',
                ],
            ]);
    }

    public function test_customer_search_matches_normalized_phone_and_limits_results_to_ten(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        for ($index = 1; $index <= 11; $index++) {
            $this->createCustomer($workshop, [
                'name' => sprintf('Driver %02d', $index),
                'phone' => sprintf('+38 (050) 111-22-%02d', $index),
                'normalized_phone' => sprintf('3805011122%02d', $index),
            ]);
        }

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->getJson(route('booking-requests.customers.search', ['q' => '+38 (050) 111-22']))
            ->assertOk()
            ->assertJsonCount(10)
            ->assertJsonPath('0.name', 'Driver 01')
            ->assertJsonPath('9.name', 'Driver 10');
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
                ->where('bookingRequest.vehicle.licensePlate', 'AA1234BB'));
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

    public function test_dashboard_booking_request_can_be_created_for_selected_customer_without_overwriting_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop, [
            'name' => 'Original Name',
            'phone' => '+1 555 111 2222',
            'normalized_phone' => '15551112222',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('booking-requests.store'), $this->validCreatePayload([
                'customer_id' => $customer->id,
                'customer_name' => 'Snapshot Name',
                'customer_phone' => '+1 555 999 0000',
                'problem_description' => 'Dashboard-created brake inspection.',
                'preferred_date' => '2026-06-20',
                'vehicle' => [
                    'brand' => 'Honda',
                    'model' => 'Civic',
                    'license_plate' => 'AA1234BB',
                ],
            ]));

        $bookingRequest = BookingRequest::query()->first();
        $vehicle = Vehicle::query()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest));

        $customer->refresh();

        $this->assertSame('Original Name', $customer->name);
        $this->assertSame('+1 555 111 2222', $customer->phone);
        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('vehicles', 1);
        $this->assertDatabaseCount('booking_requests', 1);
        $this->assertNotNull($bookingRequest);
        $this->assertNotNull($vehicle);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertSame($customer->id, $bookingRequest->customer_id);
        $this->assertSame($vehicle->id, $bookingRequest->vehicle_id);
        $this->assertSame($user->id, $bookingRequest->created_by_user_id);
        $this->assertSame('Snapshot Name', $bookingRequest->customer_name);
        $this->assertSame('+1 555 999 0000', $bookingRequest->customer_phone);
        $this->assertSame('Dashboard-created brake inspection.', $bookingRequest->problem_description);
        $this->assertSame('2026-06-20', $bookingRequest->preferred_date->toDateString());
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
        $this->assertSame($workshop->id, $vehicle->workshop_id);
        $this->assertSame($customer->id, $vehicle->customer_id);
        $this->assertSame('Honda', $vehicle->brand);
        $this->assertSame('Civic', $vehicle->model);
        $this->assertSame('AA1234BB', $vehicle->license_plate);
    }

    public function test_dashboard_booking_request_creates_new_customer_without_vehicle_when_vehicle_fields_are_empty(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('booking-requests.store'), $this->validCreatePayload([
                'customer_id' => null,
                'customer_name' => 'Jane Driver',
                'customer_phone' => '+38 (050) 111-22-33',
                'vehicle' => [
                    'brand' => '',
                    'model' => '',
                    'license_plate' => '',
                ],
            ]));

        $customer = Customer::query()->first();
        $bookingRequest = BookingRequest::query()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest));

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('booking_requests', 1);
        $this->assertNotNull($customer);
        $this->assertNotNull($bookingRequest);
        $this->assertSame($workshop->id, $customer->workshop_id);
        $this->assertSame('Jane Driver', $customer->name);
        $this->assertSame('+38 (050) 111-22-33', $customer->phone);
        $this->assertSame('380501112233', $customer->normalized_phone);
        $this->assertSame($customer->id, $bookingRequest->customer_id);
        $this->assertSame($user->id, $bookingRequest->created_by_user_id);
        $this->assertNull($bookingRequest->vehicle_id);
    }

    public function test_dashboard_booking_request_reuses_existing_customer_by_normalized_phone_without_overwriting_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop, [
            'name' => 'Original Name',
            'phone' => '+380501112233',
            'normalized_phone' => '380501112233',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('booking-requests.store'), $this->validCreatePayload([
                'customer_id' => null,
                'customer_name' => 'Later Name',
                'customer_phone' => '380 50 111 22 33',
            ]));

        $bookingRequest = BookingRequest::query()->first();

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest));

        $customer->refresh();

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame('Original Name', $customer->name);
        $this->assertSame('+380501112233', $customer->phone);
        $this->assertNotNull($bookingRequest);
        $this->assertSame($customer->id, $bookingRequest->customer_id);
        $this->assertSame('Later Name', $bookingRequest->customer_name);
        $this->assertSame('380 50 111 22 33', $bookingRequest->customer_phone);
    }

    public function test_cross_workshop_selected_customer_is_rejected(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherCustomer = $this->createCustomer($otherWorkshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('booking-requests.store'), $this->validCreatePayload([
                'customer_id' => $otherCustomer->id,
            ]))
            ->assertNotFound();

        $this->assertDatabaseCount('booking_requests', 0);
        $this->assertDatabaseCount('vehicles', 0);
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
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('status', 'Booking request status updated.');

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

            $this
                ->actingAs($user)
                ->withSession(['active_workshop_id' => $workshop->id])
                ->from(route('dashboard.booking-requests.show', $bookingRequest))
                ->patch(route('dashboard.booking-requests.status', $bookingRequest), [
                    'status' => $expectedStatus->value,
                ])
                ->assertRedirect(route('dashboard.booking-requests.show', $bookingRequest));

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
     * @param  array{name?: string, phone?: string, normalized_phone?: string}  $overrides
     */
    private function createCustomer(Workshop $workshop, array $overrides = []): Customer
    {
        $customerNumber = Customer::query()->count() + 1;

        return Customer::create([
            'workshop_id' => $workshop->id,
            'name' => $overrides['name'] ?? 'Jane Driver',
            'phone' => $overrides['phone'] ?? "+1 (555) 123-45{$customerNumber}",
            'normalized_phone' => $overrides['normalized_phone'] ?? "155512345{$customerNumber}",
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
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCreatePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'customer_id' => null,
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 (555) 123-4567',
            'problem_description' => 'Brake noise on cold start.',
            'preferred_date' => null,
            'vehicle' => [
                'brand' => null,
                'model' => null,
                'license_plate' => null,
            ],
        ], $overrides);
    }
}
