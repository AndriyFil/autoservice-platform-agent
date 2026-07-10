<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_without_workshop_membership_is_redirected_to_onboarding()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertRedirect('/workshop-onboarding');
    }

    public function test_authenticated_user_with_workshop_membership_sees_dashboard_with_active_workshop(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $this->createMembership($user, $workshop);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('activeWorkshop.id', $workshop->id)
                ->where('activeWorkshop.name', 'Main Auto')
                ->where('activeWorkshop.slug', 'main-auto')
                ->has('bookingRequests', 0));
    }

    public function test_dashboard_includes_only_booking_requests_for_active_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $this->createMembership($user, $otherWorkshop);

        $activeBookingRequest = $this->createBookingRequest($activeWorkshop, [
            'customer_name' => 'Active Customer',
        ]);
        $this->createBookingRequest($otherWorkshop, [
            'customer_name' => 'Other Customer',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('activeWorkshop.id', $activeWorkshop->id)
                ->has('bookingRequests', 1)
                ->where('bookingRequests.0.id', $activeBookingRequest->id)
                ->where('bookingRequests.0.customerName', 'Active Customer'));
    }

    public function test_invalid_session_active_workshop_id_is_repaired_and_scopes_requests_to_first_membership(): void
    {
        $user = User::factory()->create();
        $firstWorkshop = Workshop::factory()->create();
        $secondWorkshop = Workshop::factory()->create();
        $unrelatedWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $firstWorkshop);
        $this->createMembership($user, $secondWorkshop);

        $firstBookingRequest = $this->createBookingRequest($firstWorkshop, [
            'customer_name' => 'First Workshop Customer',
        ]);
        $this->createBookingRequest($secondWorkshop, [
            'customer_name' => 'Second Workshop Customer',
        ]);
        $this->createBookingRequest($unrelatedWorkshop, [
            'customer_name' => 'Unrelated Customer',
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $unrelatedWorkshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertSessionHas('active_workshop_id', $firstWorkshop->id)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('activeWorkshop.id', $firstWorkshop->id)
                ->has('bookingRequests', 1)
                ->where('bookingRequests.0.id', $firstBookingRequest->id)
                ->where('bookingRequests.0.customerName', 'First Workshop Customer'));
    }

    public function test_dashboard_represents_all_booking_request_statuses(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $createdAt = Carbon::parse('2026-06-10 10:00:00');
        $newRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::New,
            'created_at' => $createdAt->copy()->addMinutes(3),
        ]);
        $confirmedRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'created_at' => $createdAt->copy()->addMinutes(2),
        ]);
        $rejectedRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Rejected,
            'created_at' => $createdAt->copy()->addMinute(),
        ]);
        $cancelledRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Cancelled,
            'created_at' => $createdAt,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('bookingRequests', 4)
                ->where('bookingRequests.0.id', $newRequest->id)
                ->where('bookingRequests.0.status.value', 'new')
                ->where('bookingRequests.0.status.label', 'New')
                ->where('bookingRequests.1.id', $confirmedRequest->id)
                ->where('bookingRequests.1.status.value', 'confirmed')
                ->where('bookingRequests.1.status.label', 'Confirmed')
                ->where('bookingRequests.2.id', $rejectedRequest->id)
                ->where('bookingRequests.2.status.value', 'rejected')
                ->where('bookingRequests.2.status.label', 'Rejected')
                ->where('bookingRequests.3.id', $cancelledRequest->id)
                ->where('bookingRequests.3.status.value', 'cancelled')
                ->where('bookingRequests.3.status.label', 'Cancelled'));
    }

    public function test_dashboard_orders_requests_newest_first_with_id_desc_tie_breaker(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $olderRequest = $this->createBookingRequest($workshop, [
            'created_at' => Carbon::parse('2026-06-10 09:00:00'),
        ]);
        $sameTimeLowerIdRequest = $this->createBookingRequest($workshop, [
            'created_at' => Carbon::parse('2026-06-10 10:00:00'),
        ]);
        $sameTimeHigherIdRequest = $this->createBookingRequest($workshop, [
            'created_at' => Carbon::parse('2026-06-10 10:00:00'),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('bookingRequests', 3)
                ->where('bookingRequests.0.id', $sameTimeHigherIdRequest->id)
                ->where('bookingRequests.1.id', $sameTimeLowerIdRequest->id)
                ->where('bookingRequests.2.id', $olderRequest->id));
    }

    public function test_dashboard_includes_vehicle_prop_when_present_and_null_when_absent(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $requestWithVehicle = $this->createBookingRequest($workshop, [
            'created_at' => Carbon::parse('2026-06-10 10:00:00'),
            'vehicle' => [
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'AA1234BB',
            ],
        ]);
        $requestWithoutVehicle = $this->createBookingRequest($workshop, [
            'created_at' => Carbon::parse('2026-06-10 09:00:00'),
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->has('bookingRequests', 2)
                ->where('bookingRequests.0.id', $requestWithVehicle->id)
                ->where('bookingRequests.0.vehicle.brand', 'Honda')
                ->where('bookingRequests.0.vehicle.model', 'Civic')
                ->where('bookingRequests.0.vehicle.licensePlate', 'AA1234BB')
                ->where('bookingRequests.1.id', $requestWithoutVehicle->id)
                ->where('bookingRequests.1.vehicle', null));
    }

    public function test_dashboard_includes_new_public_intake_requests_for_active_workshop(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $bookingRequest = BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => '380501112233',
            'problem_description' => 'Opel Insignia, check engine light came on.',
            'original_message' => 'Opel Insignia, check engine light came on.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::New,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('activeWorkshop.id', $workshop->id)
                ->has('bookingRequests', 1)
                ->where('bookingRequests.0.id', $bookingRequest->id)
                ->where('bookingRequests.0.status.value', 'new')
                ->where('bookingRequests.0.problemDescription', 'Opel Insignia, check engine light came on.')
                ->missing('unassignedIntakeRequests'));
    }

    public function test_dashboard_does_not_include_other_workshop_new_public_intake_requests(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $this->createMembership($user, $otherWorkshop);

        $activeBookingRequest = BookingRequest::create([
            'workshop_id' => $activeWorkshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => '380501112233',
            'problem_description' => 'Active workshop request.',
            'original_message' => 'Active workshop request.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::New,
        ]);

        BookingRequest::create([
            'workshop_id' => $otherWorkshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => '380671112233',
            'problem_description' => 'Other workshop request.',
            'original_message' => 'Other workshop request.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::New,
        ]);

        $response = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get('/dashboard');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('activeWorkshop.id', $activeWorkshop->id)
                ->has('bookingRequests', 1)
                ->where('bookingRequests.0.id', $activeBookingRequest->id)
                ->where('bookingRequests.0.problemDescription', 'Active workshop request.'));
    }

    private function createMembership(User $user, Workshop $workshop): WorkshopUser
    {
        return WorkshopUser::create([
            'user_id' => $user->id,
            'workshop_id' => $workshop->id,
            'role' => WorkshopUserRole::Owner,
        ]);
    }

    /**
     * @param  array{
     *     customer_name?: string,
     *     customer_phone?: string,
     *     problem_description?: string,
     *     preferred_date?: string|null,
     *     status?: BookingRequestStatus,
     *     created_at?: Carbon,
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

        $bookingRequest = BookingRequest::create([
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

        if (isset($overrides['created_at'])) {
            $bookingRequest->forceFill([
                'created_at' => $overrides['created_at'],
                'updated_at' => $overrides['created_at'],
            ])->save();
        }

        return $bookingRequest->refresh();
    }
}
