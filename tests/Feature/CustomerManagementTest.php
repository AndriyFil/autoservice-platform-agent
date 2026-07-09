<?php

namespace Tests\Feature;

use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\BookingRequestStatus;
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

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('customers.index'))
            ->assertRedirect('/login');

        $this->get(route('customers.show', $customer))
            ->assertRedirect('/login');

        $this->patch(route('customers.update', $customer))
            ->assertRedirect('/login');
    }

    public function test_user_without_workshop_membership_is_redirected_to_onboarding(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('customers.index'))
            ->assertRedirect('/workshop-onboarding');

        $this
            ->actingAs($user)
            ->get(route('customers.show', $customer))
            ->assertRedirect('/workshop-onboarding');
    }

    public function test_customer_list_shows_only_active_workshop_customers(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $this->createMembership($user, $otherWorkshop);

        $activeCustomer = $this->createCustomer($activeWorkshop, [
            'name' => 'Active Customer',
            'phone' => '+1 555 111 2222',
            'normalized_phone' => '15551112222',
        ]);
        $this->createVehicle($activeWorkshop, $activeCustomer);
        $this->createVehicle($activeWorkshop, $activeCustomer, [
            'brand' => 'Toyota',
            'model' => 'Camry',
            'license_plate' => 'AA2222BB',
        ]);
        $this->createBookingRequest($activeWorkshop, $activeCustomer, [
            'created_at' => Carbon::parse('2026-06-10 09:00:00'),
        ]);
        $latestBookingRequest = $this->createBookingRequest($activeWorkshop, $activeCustomer, [
            'created_at' => Carbon::parse('2026-06-11 10:00:00'),
        ]);

        $otherCustomer = $this->createCustomer($otherWorkshop, [
            'name' => 'Other Customer',
            'phone' => '+1 555 333 4444',
            'normalized_phone' => '15553334444',
        ]);
        $this->createBookingRequest($otherWorkshop, $otherCustomer);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('customers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Index')
                ->where('activeWorkshop.id', $activeWorkshop->id)
                ->where('activeWorkshop.name', 'Main Auto')
                ->has('customers', 1)
                ->where('customers.0.id', $activeCustomer->id)
                ->where('customers.0.name', 'Active Customer')
                ->where('customers.0.phone', '+1 555 111 2222')
                ->where('customers.0.vehiclesCount', 2)
                ->where('customers.0.bookingRequestsCount', 2)
                ->where('customers.0.latestBookingRequestDate', $latestBookingRequest->created_at->toISOString()));
    }

    public function test_customer_detail_works_for_active_workshop_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $this->createMembership($user, $workshop);

        $customer = $this->createCustomer($workshop, [
            'name' => 'Jane Driver',
            'phone' => '+1 555 123 4567',
        ]);
        $vehicle = $this->createVehicle($workshop, $customer, [
            'brand' => 'Honda',
            'model' => 'Civic',
            'license_plate' => 'AA1234BB',
        ]);
        $bookingRequest = $this->createBookingRequest($workshop, $customer, [
            'vehicle_id' => $vehicle->id,
            'status' => BookingRequestStatus::Confirmed,
            'problem_description' => 'Brake noise on cold start.',
            'preferred_date' => '2026-06-20',
            'created_at' => Carbon::parse('2026-06-10 10:00:00'),
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->where('activeWorkshop.id', $workshop->id)
                ->where('customer.id', $customer->id)
                ->where('customer.name', 'Jane Driver')
                ->where('customer.phone', '+1 555 123 4567')
                ->where('customer.createdAt', $customer->created_at->toISOString())
                ->has('customer.vehicles', 1)
                ->where('customer.vehicles.0.brand', 'Honda')
                ->where('customer.vehicles.0.model', 'Civic')
                ->where('customer.vehicles.0.year', null)
                ->where('customer.vehicles.0.licensePlate', 'AA1234BB')
                ->has('customer.bookingRequests', 1)
                ->where('customer.bookingRequests.0.id', $bookingRequest->id)
                ->where('customer.bookingRequests.0.status.value', 'confirmed')
                ->where('customer.bookingRequests.0.status.label', 'Confirmed')
                ->where('customer.bookingRequests.0.problemDescription', 'Brake noise on cold start.')
                ->where('customer.bookingRequests.0.preferredDate', '2026-06-20')
                ->where('customer.bookingRequests.0.createdAt', $bookingRequest->created_at->toISOString())
                ->has('customer.repairOrders', 0));
    }

    public function test_customer_detail_returns_not_found_for_another_workshop_customer(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);

        $otherCustomer = $this->createCustomer($otherWorkshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->get(route('customers.show', $otherCustomer))
            ->assertNotFound();
    }

    public function test_customer_detail_includes_vehicles_and_booking_requests(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);

        $customer = $this->createCustomer($workshop);
        $olderVehicle = $this->createVehicle($workshop, $customer, [
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'license_plate' => 'AA1111BB',
        ]);
        $newerVehicle = $this->createVehicle($workshop, $customer, [
            'brand' => 'Volkswagen',
            'model' => 'Golf',
            'license_plate' => 'AA2222BB',
        ]);
        $olderBookingRequest = $this->createBookingRequest($workshop, $customer, [
            'vehicle_id' => $olderVehicle->id,
            'problem_description' => 'Oil leak.',
            'created_at' => Carbon::parse('2026-06-10 09:00:00'),
        ]);
        $newerBookingRequest = $this->createBookingRequest($workshop, $customer, [
            'vehicle_id' => $newerVehicle->id,
            'problem_description' => 'Battery drain.',
            'created_at' => Carbon::parse('2026-06-11 09:00:00'),
        ]);
        $repairOrder = RepairOrder::factory()->create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $newerVehicle->id,
            'problem_description' => 'Battery diagnostic.',
            'opened_at' => Carbon::parse('2026-06-12 09:00:00'),
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customers/Show')
                ->has('customer.vehicles', 2)
                ->where('customer.vehicles.0.id', $olderVehicle->id)
                ->where('customer.vehicles.1.id', $newerVehicle->id)
                ->has('customer.bookingRequests', 2)
                ->where('customer.bookingRequests.0.id', $newerBookingRequest->id)
                ->where('customer.bookingRequests.0.problemDescription', 'Battery drain.')
                ->where('customer.bookingRequests.1.id', $olderBookingRequest->id)
                ->where('customer.bookingRequests.1.problemDescription', 'Oil leak.')
                ->has('customer.repairOrders', 1)
                ->where('customer.repairOrders.0.id', $repairOrder->id)
                ->where('customer.repairOrders.0.vehicle.id', $newerVehicle->id));
    }

    public function test_staff_can_update_customer_name(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop, [
            'name' => 'Old Name',
            'phone' => '+1 555 100 2000',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('customers.update', $customer), [
                'name' => 'New Name',
                'phone' => '+1 555 100 2000',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame('New Name', $customer->refresh()->name);
    }

    public function test_staff_can_update_customer_phone_and_recalculate_phone_normalized(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop, [
            'phone' => '0685620040',
        ]);

        $this->assertSame('+380685620040', $customer->phone_normalized);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('customers.update', $customer), [
                'name' => $customer->name,
                'phone' => '+38 (050) 111-22-33',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame('+380501112233', $customer->refresh()->phone_normalized);
        $this->assertSame('+38 (050) 111-22-33', $customer->phone);
    }

    public function test_duplicate_phone_normalized_in_same_workshop_is_blocked(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $existingCustomer = $this->createCustomer($workshop, [
            'phone' => '0685620040',
        ]);
        $customer = $this->createCustomer($workshop, [
            'phone' => '+1 555 999 0000',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('customers.show', $customer))
            ->patch(route('customers.update', $customer), [
                'name' => $customer->name,
                'phone' => '+38 (068) 562-00-40',
            ])
            ->assertRedirect(route('customers.show', $customer))
            ->assertSessionHasErrors('phone');

        $this->assertSame('+380685620040', $existingCustomer->refresh()->phone_normalized);
        $this->assertSame('+1 555 999 0000', $customer->refresh()->phone);
    }

    public function test_same_phone_normalized_in_another_workshop_is_allowed(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $this->createCustomer($otherWorkshop, [
            'phone' => '0685620040',
        ]);
        $customer = $this->createCustomer($workshop, [
            'phone' => '+1 555 999 0000',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('customers.update', $customer), [
                'name' => $customer->name,
                'phone' => '+38 (068) 562-00-40',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame('+380685620040', $customer->refresh()->phone_normalized);
    }

    public function test_staff_cannot_update_customer_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $otherCustomer = $this->createCustomer($otherWorkshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('customers.update', $otherCustomer), [
                'name' => 'Leaked Name',
                'phone' => '+1 555 444 3333',
            ])
            ->assertNotFound();
    }

    public function test_staff_can_add_vehicle_to_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('customers.vehicles.store', $customer), [
                'make' => 'Opel',
                'model' => 'Insignia',
                'year' => 2018,
                'plate' => 'AA7777BB',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('vehicles', [
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => 'Opel',
            'model' => 'Insignia',
            'year' => 2018,
            'license_plate' => 'AA7777BB',
        ]);
    }

    public function test_staff_can_update_vehicle(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop);
        $vehicle = $this->createVehicle($workshop, $customer);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('customers.vehicles.update', [$customer, $vehicle]), [
                'make' => 'Skoda',
                'model' => 'Octavia',
                'year' => 2020,
                'plate' => 'AA8888BB',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $vehicle->refresh();

        $this->assertSame('Skoda', $vehicle->brand);
        $this->assertSame('Octavia', $vehicle->model);
        $this->assertSame(2020, $vehicle->year);
        $this->assertSame('AA8888BB', $vehicle->license_plate);
    }

    public function test_staff_cannot_update_vehicle_from_another_customer(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop);
        $otherCustomer = $this->createCustomer($workshop);
        $vehicle = $this->createVehicle($workshop, $otherCustomer);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('customers.vehicles.update', [$customer, $vehicle]), [
                'make' => 'Skoda',
                'model' => 'Octavia',
                'year' => 2020,
                'plate' => 'AA8888BB',
            ])
            ->assertNotFound();
    }

    public function test_staff_cannot_update_vehicle_from_another_workshop(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop);
        $customer = $this->createCustomer($activeWorkshop);
        $otherCustomer = $this->createCustomer($otherWorkshop);
        $vehicle = $this->createVehicle($otherWorkshop, $otherCustomer);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->patch(route('customers.vehicles.update', [$customer, $vehicle]), [
                'make' => 'Skoda',
                'model' => 'Octavia',
                'year' => 2020,
                'plate' => 'AA8888BB',
            ])
            ->assertNotFound();
    }

    public function test_customer_management_does_not_create_or_modify_users(): void
    {
        $user = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
        ]);
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $customer = $this->createCustomer($workshop);
        $userCount = User::count();

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->patch(route('customers.update', $customer), [
                'name' => 'Updated Customer',
                'phone' => '+1 555 100 2000',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('customers.vehicles.store', $customer), [
                'make' => 'Opel',
                'model' => 'Astra',
                'year' => 2017,
                'plate' => 'AA5555BB',
            ])
            ->assertRedirect(route('customers.show', $customer));

        $this->assertSame($userCount, User::count());
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Staff User',
            'email' => 'staff@example.com',
        ]);
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
     * @param  array{brand?: string|null, model?: string|null, year?: int|null, license_plate?: string|null}  $overrides
     */
    private function createVehicle(Workshop $workshop, Customer $customer, array $overrides = []): Vehicle
    {
        return Vehicle::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'brand' => $overrides['brand'] ?? 'Honda',
            'model' => $overrides['model'] ?? 'Civic',
            'year' => $overrides['year'] ?? null,
            'license_plate' => $overrides['license_plate'] ?? 'AA1234BB',
        ]);
    }

    /**
     * @param  array{
     *     vehicle_id?: int|null,
     *     status?: BookingRequestStatus,
     *     problem_description?: string,
     *     preferred_date?: string|null,
     *     created_at?: Carbon
     * }  $overrides
     */
    private function createBookingRequest(Workshop $workshop, Customer $customer, array $overrides = []): BookingRequest
    {
        $bookingRequest = BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $overrides['vehicle_id'] ?? null,
            'created_by_user_id' => null,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
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
