<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\CustomerPortal\Queries\CustomerRequestIndexQuery;
use App\Models\BookingRequest;
use App\Models\Workshop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerPortalRequestHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_returns_only_exact_phone_requests_newest_first_with_safe_fields(): void
    {
        CarbonImmutable::setTestNow('2026-07-18 12:00:00');

        $workshop = Workshop::factory()->create(['name' => 'Main Auto']);
        $older = BookingRequest::factory()->for($workshop)->create([
            'customer_phone' => '+380501112233',
            'problem_description' => '',
            'original_message' => '',
            'status' => BookingRequestStatus::Confirmed,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subHour(),
        ]);
        $newer = BookingRequest::factory()->for($workshop)->create([
            'customer_phone' => '+380501112233',
            'problem_description' => 'Brake noise',
            'status' => BookingRequestStatus::New,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        BookingRequest::factory()->for($workshop)->create([
            'customer_phone' => '+380509999999',
            'problem_description' => 'Private request',
            'created_at' => now()->addMinute(),
        ]);

        $history = app(CustomerRequestIndexQuery::class)->handle('+380501112233');

        $this->assertSame([$newer->id, $older->id], array_column($history['recent'], 'id'));
        $this->assertSame([$newer->id, $older->id], $history['requests']->pluck('id')->all());
        $this->assertSame('Brake noise', $history['recent'][0]['title']);
        $this->assertSame(['value' => 'new', 'label' => 'New'], $history['recent'][0]['status']);
        $this->assertSame('Main Auto', $history['recent'][0]['workshopName']);
        $this->assertSame(now()->toIso8601String(), $history['recent'][0]['submittedAt']);
        $this->assertSame(now()->toIso8601String(), $history['recent'][0]['updatedAt']);
        $this->assertSame('Service request', $history['recent'][1]['title']);
        $this->assertFalse($history['hasMore']);
        $this->assertArrayNotHasKey('customer_phone', $history['recent'][0]);
        $this->assertArrayNotHasKey('customer_phone_normalized', $history['recent'][0]);
    }

    public function test_query_caps_recent_requests_at_ten_and_reports_more(): void
    {
        $workshop = Workshop::factory()->create();

        foreach (range(1, 11) as $position) {
            BookingRequest::factory()->for($workshop)->create([
                'customer_phone' => '+380501112233',
                'created_at' => now()->subMinutes($position),
            ]);
        }

        $history = app(CustomerRequestIndexQuery::class)->handle('+380501112233');

        $this->assertCount(10, $history['recent']);
        $this->assertTrue($history['hasMore']);
        $this->assertSame(11, $history['requests']->total());
    }

    public function test_query_returns_empty_history_for_a_phone_without_requests(): void
    {
        $history = app(CustomerRequestIndexQuery::class)->handle('+380501112233');

        $this->assertSame([], $history['recent']);
        $this->assertFalse($history['hasMore']);
        $this->assertSame(0, $history['requests']->total());
    }

    public function test_verified_customer_receives_only_requests_for_the_verified_phone(): void
    {
        $workshop = Workshop::factory()->create(['name' => 'Main Auto']);
        $owned = BookingRequest::factory()->for($workshop)->create([
            'customer_phone' => '+380501112233',
            'problem_description' => 'Brake noise',
        ]);
        BookingRequest::factory()->for($workshop)->create([
            'customer_phone' => '+380509999999',
            'problem_description' => 'Private request',
        ]);

        $response = $this->withSession($this->activeVerifiedSession('+380501112233'))
            ->get('/my-requests');

        $response->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/Index')
                ->has('recentRequests', 1)
                ->where('recentRequests.0.id', $owned->id)
                ->where('recentRequests.0.title', 'Brake noise')
                ->where('recentRequests.0.workshopName', 'Main Auto')
                ->where('hasMoreRequests', false)
                ->has('requests.data', 1)
                ->missing('phone')
                ->missing('verifiedPhone'));
        $response->assertDontSee('Private request')
            ->assertDontSee('+380501112233')
            ->assertDontSee('+380509999999');
    }

    public function test_verified_customer_receives_empty_request_history(): void
    {
        $this->withSession($this->activeVerifiedSession('+380501112233'))
            ->get('/my-requests')
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/Index')
                ->has('recentRequests', 0)
                ->where('hasMoreRequests', false)
                ->has('requests.data', 0));
    }

    public function test_verified_customer_can_open_an_owned_request(): void
    {
        $workshop = Workshop::factory()->create(['name' => 'Main Auto']);
        $bookingRequest = BookingRequest::factory()->for($workshop)->create([
            'customer_phone' => '+380501112233',
            'problem_description' => 'Brake noise',
            'customer_name' => 'Olena',
            'vehicle_brand' => 'Toyota',
            'vehicle_model' => 'Corolla',
            'vehicle_year' => 2018,
            'vehicle_license_plate' => 'AA 1234 BB',
        ]);

        $this->withSession($this->activeVerifiedSession('+380501112233'))
            ->get("/my-requests/{$bookingRequest->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('CustomerPortal/Show', false)
                ->where('request.id', $bookingRequest->id)
                ->where('request.problemDescription', 'Brake noise')
                ->where('request.workshopName', 'Main Auto')
                ->where('request.customerName', 'Olena')
                ->where('request.vehicle.brand', 'Toyota')
                ->has('recentRequests', 1)
                ->where('hasMoreRequests', false)
                ->missing('request.customerPhone')
                ->missing('request.repairOrder'));
    }

    public function test_verified_customer_receives_404_for_another_phone_request(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'customer_phone' => '+380509999999',
        ]);

        $this->withSession($this->activeVerifiedSession('+380501112233'))
            ->get("/my-requests/{$bookingRequest->id}")
            ->assertNotFound();
    }

    public function test_unverified_customer_is_redirected_before_request_detail_is_resolved(): void
    {
        $bookingRequest = BookingRequest::factory()->create([
            'customer_phone' => '+380501112233',
        ]);

        $this->get("/my-requests/{$bookingRequest->id}")
            ->assertRedirect('/my-requests/access');
    }

    /** @return array<string, int|string> */
    private function activeVerifiedSession(string $phone): array
    {
        return [
            'customer_portal.verified_phone' => $phone,
            'customer_portal.verified_until' => now()->addMinutes(10)->timestamp,
        ];
    }
}
