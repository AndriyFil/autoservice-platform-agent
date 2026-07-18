<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\CustomerPortal\Queries\CustomerRequestIndexQuery;
use App\Models\BookingRequest;
use App\Models\Workshop;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
