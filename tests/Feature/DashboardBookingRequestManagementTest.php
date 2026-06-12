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

}
