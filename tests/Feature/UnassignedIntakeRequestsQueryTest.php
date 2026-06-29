<?php

namespace Tests\Feature;

use App\Enums\BookingRequestStatus;
use App\Enums\MissingIntakeField;
use App\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use App\Queries\Admin\UnassignedIntakeRequestsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UnassignedIntakeRequestsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_query_returns_only_unassigned_submitted_intake_requests(): void
    {
        $owner = $this->createMembership(WorkshopUserRole::Owner);
        $included = $this->createUnassignedSubmittedIntake([
            'customer_phone' => '380501112233',
            'created_at' => Carbon::parse('2026-06-20 09:00:00'),
        ]);

        $this->createAssignedSubmittedIntake();
        $this->createUnassignedSubmittedIntake([
            'status' => BookingRequestStatus::New,
        ]);

        $results = $this->query()->handle($owner);

        $this->assertCount(1, $results);
        $this->assertSame($included->id, $results[0]['id']);
        $this->assertSame('380501112233', $results[0]['customerPhone']);
        $this->assertSame(MissingIntakeField::Vehicle->label(), $results[0]['missingNextField']['label']);
        $this->assertSame('Needs review', $results[0]['status']['label']);
    }

    public function test_staff_query_cannot_see_unassigned_submitted_intake_requests(): void
    {
        $staff = $this->createMembership(WorkshopUserRole::Staff);

        $this->createUnassignedSubmittedIntake([
            'customer_phone' => '380501112233',
        ]);

        $this->assertSame([], $this->query()->handle($staff));
    }

    public function test_query_orders_oldest_first_and_applies_limit(): void
    {
        $owner = $this->createMembership(WorkshopUserRole::Owner);
        $older = $this->createUnassignedSubmittedIntake([
            'created_at' => Carbon::parse('2026-06-20 09:00:00'),
        ]);
        $newer = $this->createUnassignedSubmittedIntake([
            'created_at' => Carbon::parse('2026-06-20 10:00:00'),
        ]);

        $results = $this->query()->handle($owner, limit: 1);

        $this->assertCount(1, $results);
        $this->assertSame($older->id, $results[0]['id']);
        $this->assertNotSame($newer->id, $results[0]['id']);
    }

    public function test_query_shape_preserves_original_message_and_safe_missing_field_label(): void
    {
        $owner = $this->createMembership(WorkshopUserRole::Owner);
        $bookingRequest = $this->createUnassignedSubmittedIntake([
            'customer_phone' => null,
            'original_message' => '  Opel Insignia, check engine light came on.  ',
            'problem_description' => '  Opel Insignia, check engine light came on.  ',
            'created_at' => Carbon::parse('2026-06-20 09:00:00'),
        ]);

        $results = $this->query()->handle($owner);

        $this->assertSame($bookingRequest->id, $results[0]['id']);
        $this->assertSame('2026-06-20T09:00:00.000000Z', $results[0]['receivedAt']);
        $this->assertSame('  Opel Insignia, check engine light came on.  ', $results[0]['originalMessage']);
        $this->assertSame('  Opel Insignia, check engine light came on.  ', $results[0]['problemSummary']);
        $this->assertNull($results[0]['customerPhone']);
        $this->assertNull($results[0]['vehicle']);
        $this->assertSame(MissingIntakeField::Phone->value, $results[0]['missingNextField']['value']);
        $this->assertSame(MissingIntakeField::Phone->label(), $results[0]['missingNextField']['label']);
        $this->assertSame('submitted', $results[0]['status']['value']);
        $this->assertSame('Needs review', $results[0]['status']['label']);
    }

    private function query(): UnassignedIntakeRequestsQuery
    {
        return $this->app->make(UnassignedIntakeRequestsQuery::class);
    }

    private function createMembership(WorkshopUserRole $role): WorkshopUser
    {
        return WorkshopUser::create([
            'user_id' => User::factory()->create()->id,
            'workshop_id' => Workshop::factory()->create()->id,
            'role' => $role,
        ]);
    }

    /**
     * @param  array{
     *     customer_phone?: string|null,
     *     original_message?: string|null,
     *     problem_description?: string|null,
     *     status?: BookingRequestStatus,
     *     created_at?: Carbon
     * }  $overrides
     */
    private function createUnassignedSubmittedIntake(array $overrides = []): BookingRequest
    {
        $createdAt = $overrides['created_at'] ?? Carbon::parse('2026-06-20 09:00:00');

        $bookingRequest = BookingRequest::create([
            'workshop_id' => null,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => $overrides['customer_phone'] ?? null,
            'problem_description' => $overrides['problem_description'] ?? 'Opel Insignia, check engine light came on.',
            'original_message' => $overrides['original_message'] ?? 'Opel Insignia, check engine light came on.',
            'preferred_date' => null,
            'status' => $overrides['status'] ?? BookingRequestStatus::Submitted,
        ]);

        $bookingRequest->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();

        return $bookingRequest->refresh();
    }

    private function createAssignedSubmittedIntake(): BookingRequest
    {
        $workshop = Workshop::factory()->create();

        return BookingRequest::create([
            'workshop_id' => $workshop->id,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => '380501112233',
            'problem_description' => 'Assigned request.',
            'original_message' => 'Assigned request.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::Submitted,
        ]);
    }
}
