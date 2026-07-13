<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BookingRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_booking_request_duplicate_posts_keep_single_repair_order(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop, WorkshopUserRole::Staff);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::Confirmed,
            'customer_id' => null,
            'customer_phone' => '+1 555 111 2222',
            'problem_description' => 'Confirmed request problem.',
        ]);
        $usersBefore = User::query()->count();

        $firstResponse = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Internal confirmed request problem.',
            ]);

        $repairOrder = RepairOrder::query()->sole();

        $firstResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $this->assertSame($bookingRequest->id, $repairOrder->booking_request_id);
        $this->assertSame($workshop->id, $repairOrder->workshop_id);
        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->status);
        $this->assertSame('Internal confirmed request problem.', $repairOrder->problem_description);
        $this->assertSame($usersBefore, User::query()->count());

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Duplicate confirmed request problem.',
            ])
            ->assertRedirect(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertSessionHasErrors('repair_order');

        $this->assertSame(1, RepairOrder::query()->where('booking_request_id', $bookingRequest->id)->count());
        $this->assertSame($repairOrder->id, RepairOrder::query()->where('booking_request_id', $bookingRequest->id)->sole()->id);
        $this->assertSame($usersBefore, User::query()->count());

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

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

    public function test_new_booking_request_duplicate_posts_keep_single_repair_order_after_internal_confirmation(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop, WorkshopUserRole::Staff);
        $bookingRequest = $this->createBookingRequest($workshop, [
            'status' => BookingRequestStatus::New,
            'customer_id' => null,
            'customer_phone' => '+1 555 333 4444',
            'problem_description' => 'New request problem.',
        ]);
        $usersBefore = User::query()->count();

        $firstResponse = $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Internal new request problem.',
            ]);

        $repairOrder = RepairOrder::query()->sole();

        $firstResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $this->assertSame(BookingRequestStatus::Confirmed, $bookingRequest->refresh()->status);
        $this->assertSame($bookingRequest->id, $repairOrder->booking_request_id);
        $this->assertSame($workshop->id, $repairOrder->workshop_id);
        $this->assertSame($usersBefore, User::query()->count());

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => 'Duplicate new request problem.',
            ])
            ->assertRedirect(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertSessionHasErrors('repair_order');

        $this->assertSame(1, RepairOrder::query()->where('booking_request_id', $bookingRequest->id)->count());
        $this->assertSame($repairOrder->id, RepairOrder::query()->where('booking_request_id', $bookingRequest->id)->sole()->id);
        $this->assertSame($usersBefore, User::query()->count());

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.create', ['booking_request' => $bookingRequest->id]))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));
    }

    public function test_cross_workshop_duplicate_conversion_attempt_is_forbidden(): void
    {
        $user = User::factory()->create();
        $activeWorkshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $activeWorkshop, WorkshopUserRole::Staff);
        $otherBookingRequest = $this->createBookingRequest($otherWorkshop, [
            'status' => BookingRequestStatus::Confirmed,
        ]);
        $otherRepairOrder = $this->createRepairOrder($otherBookingRequest);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $activeWorkshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $otherBookingRequest->id,
                'problem_description' => 'Cross workshop duplicate attempt.',
            ])
            ->assertNotFound();

        $this->assertSame(1, RepairOrder::query()->where('booking_request_id', $otherBookingRequest->id)->count());
        $this->assertSame($otherRepairOrder->id, RepairOrder::query()->where('booking_request_id', $otherBookingRequest->id)->sole()->id);
    }

    public function test_database_unique_constraint_is_final_safety_net_for_booking_request_repair_order_link(): void
    {
        $indexes = collect(DB::select(<<<'SQL'
            SELECT indexname, indexdef
            FROM pg_indexes
            WHERE schemaname = current_schema()
              AND tablename = 'repair_orders'
        SQL));

        $uniqueBookingRequestIndex = $indexes->first(fn (object $index): bool => str_contains($index->indexdef, 'UNIQUE')
            && str_contains($index->indexdef, '(booking_request_id)'));

        $this->assertNotNull($uniqueBookingRequestIndex);
    }

    /**
     * @param  array{
     *     customer_id?: int|null,
     *     customer_name?: string|null,
     *     customer_phone?: string,
     *     problem_description?: string,
     *     status?: BookingRequestStatus
     * }  $overrides
     */
    private function createBookingRequest(Workshop $workshop, array $overrides = []): BookingRequest
    {
        $customer = null;
        $customerName = $overrides['customer_name'] ?? 'Jane Driver';
        $customerPhone = $overrides['customer_phone'] ?? '+1 555 123 4567';

        if (! array_key_exists('customer_id', $overrides)) {
            $customer = Customer::create([
                'workshop_id' => $workshop->id,
                'name' => $customerName,
                'phone' => $customerPhone,
            ]);
        }

        $problemDescription = $overrides['problem_description'] ?? 'Brake noise on cold start.';

        return BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => $overrides['customer_id'] ?? $customer?->id,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'problem_description' => $problemDescription,
            'original_message' => $problemDescription,
            'preferred_date' => null,
            'status' => $overrides['status'] ?? BookingRequestStatus::New,
        ]);
    }

    /**
     * @param  array{problem_description?: string}  $overrides
     */
    private function createRepairOrder(BookingRequest $bookingRequest, array $overrides = []): RepairOrder
    {
        return RepairOrder::create([
            'workshop_id' => $bookingRequest->workshop_id,
            'customer_id' => $bookingRequest->customer_id,
            'vehicle_id' => $bookingRequest->vehicle_id,
            'booking_request_id' => $bookingRequest->id,
            'status' => RepairOrderStatus::Draft,
            'requires_estimate_approval' => true,
            'problem_description' => $overrides['problem_description'] ?? $bookingRequest->problem_description,
            'opened_at' => now(),
            'closed_at' => null,
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
}
