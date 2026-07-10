<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicBookingRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_user_can_open_booking_form_without_authentication(): void
    {
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        $response = $this->get('/book/main-auto');

        $response
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicBookingRequests/Create')
                ->where('workshop.name', $workshop->name)
                ->where('workshop.slug', $workshop->slug));

        $this->assertGuest();
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_unknown_workshop_slug_returns_404(): void
    {
        $this->get('/book/missing-workshop')->assertNotFound();

        $this->post('/book/missing-workshop', $this->validPayload())->assertNotFound();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_public_submission_creates_booking_request_without_customer_or_vehicle(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);

        $response = $this->post('/book/main-auto', $this->validPayload([
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 (555) 123-4567',
            'problem_description' => 'Brake noise on cold start.',
            'preferred_date' => null,
            'vehicle' => [
                'brand' => '',
                'model' => '',
                'license_plate' => '',
            ],
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');

        $bookingRequest = BookingRequest::first();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('booking_requests', 1);

        $this->assertNotNull($bookingRequest);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertNull($bookingRequest->created_by_user_id);
        $this->assertSame('Jane Driver', $bookingRequest->customer_name);
        $this->assertSame('+1 (555) 123-4567', $bookingRequest->customer_phone);
        $this->assertSame('+15551234567', $bookingRequest->customer_phone_normalized);
        $this->assertSame('Brake noise on cold start.', $bookingRequest->problem_description);
        $this->assertSame('Brake noise on cold start.', $bookingRequest->original_message);
        $this->assertNull($bookingRequest->preferred_date);
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
    }

    public function test_phone_is_normalized_on_booking_request_without_customer_lookup(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);
        Customer::factory()->create([
            'workshop_id' => $workshop->id,
            'name' => 'Existing Driver',
            'phone' => '380501112233',
            'normalized_phone' => '380501112233',
        ]);

        $response = $this->post('/book/main-auto', $this->validPayload([
            'customer_name' => 'Formatted Driver',
            'customer_phone' => '+38 (050) 111-22-33',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');

        $bookingRequest = BookingRequest::first();

        $this->assertDatabaseCount('customers', 1);
        $this->assertNotNull($bookingRequest);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertSame('+38 (050) 111-22-33', $bookingRequest->customer_phone);
        $this->assertSame('+380501112233', $bookingRequest->customer_phone_normalized);
    }

    public function test_existing_customer_is_not_linked_during_public_submission(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);
        Customer::factory()->create([
            'workshop_id' => $workshop->id,
            'phone' => '380501112233',
            'phone_normalized' => '+380501112233',
            'normalized_phone' => '380501112233',
        ]);

        $response = $this->post('/book/main-auto', $this->validPayload([
            'customer_phone' => '+38 (050) 111-22-33',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');

        $bookingRequest = BookingRequest::first();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('booking_requests', 1);
        $this->assertNotNull($bookingRequest);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertSame('+380501112233', $bookingRequest->customer_phone_normalized);
    }

    public function test_ukrainian_phone_variants_are_normalized_without_creating_customer(): void
    {
        Workshop::factory()->create(['slug' => 'main-auto']);

        foreach ([
            '0685620040',
            '+380685620040',
            '380685620040',
            '068 562 00 40',
            '+38 (068) 562-00-40',
        ] as $phone) {
            $this->post('/book/main-auto', $this->validPayload([
                'customer_phone' => $phone,
            ]))->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('booking_requests', 5);
        $this->assertSame(
            5,
            BookingRequest::query()
                ->whereNull('customer_id')
                ->where('customer_phone_normalized', '+380685620040')
                ->count(),
        );
    }

    public function test_existing_customer_name_and_phone_are_not_overwritten_by_later_public_submissions(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);
        $customer = Customer::factory()->create([
            'workshop_id' => $workshop->id,
            'name' => 'Original Name',
            'phone' => '+380501112233',
            'normalized_phone' => '380501112233',
        ]);

        $response = $this->post('/book/main-auto', $this->validPayload([
            'customer_name' => 'Later Name',
            'customer_phone' => '380 50 111 22 33',
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');

        $customer->refresh();
        $bookingRequest = BookingRequest::first();

        $this->assertSame('Original Name', $customer->name);
        $this->assertSame('+380501112233', $customer->phone);
        $this->assertSame('+380501112233', $customer->phone_normalized);
        $this->assertNotNull($bookingRequest);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertSame('Later Name', $bookingRequest->customer_name);
        $this->assertSame('380 50 111 22 33', $bookingRequest->customer_phone);
        $this->assertSame('+380501112233', $bookingRequest->customer_phone_normalized);
    }

    public function test_vehicle_fields_do_not_create_vehicle_during_public_submission(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);

        $response = $this->post('/book/main-auto', $this->validPayload([
            'vehicle' => [
                'brand' => '',
                'model' => 'Civic',
                'license_plate' => '',
            ],
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');

        $bookingRequest = BookingRequest::first();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertNotNull($bookingRequest);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertSame('Brake noise on cold start.', $bookingRequest->problem_description);
    }

    public function test_booking_request_persists_expected_public_submission_fields(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);

        $response = $this->post('/book/main-auto', $this->validPayload([
            'customer_name' => 'Jane Driver',
            'customer_phone' => '+1 555 123 4567',
            'problem_description' => 'Engine warning light is on.',
            'preferred_date' => '2026-06-20',
            'vehicle' => [
                'brand' => 'Honda',
                'model' => 'Civic',
                'license_plate' => 'AA1234BB',
            ],
        ]));

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');

        $bookingRequest = BookingRequest::first();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertNotNull($bookingRequest);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertNull($bookingRequest->created_by_user_id);
        $this->assertSame('Jane Driver', $bookingRequest->customer_name);
        $this->assertSame('+1 555 123 4567', $bookingRequest->customer_phone);
        $this->assertSame('+15551234567', $bookingRequest->customer_phone_normalized);
        $this->assertSame('Engine warning light is on.', $bookingRequest->problem_description);
        $this->assertSame('Engine warning light is on.', $bookingRequest->original_message);
        $this->assertSame('2026-06-20', $bookingRequest->preferred_date->toDateString());
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
    }

    public function test_same_phone_in_different_workshops_creates_separate_booking_requests(): void
    {
        $firstWorkshop = Workshop::factory()->create(['slug' => 'main-auto']);
        $secondWorkshop = Workshop::factory()->create(['slug' => 'second-auto']);

        $firstResponse = $this->post('/book/main-auto', $this->validPayload([
            'customer_phone' => '+38 (050) 111-22-33',
        ]));

        $secondResponse = $this->post('/book/second-auto', $this->validPayload([
            'customer_phone' => '380501112233',
        ]));

        $firstResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/main-auto/success');
        $secondResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect('/book/second-auto/success');

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('booking_requests', 2);

        $this->assertDatabaseHas('booking_requests', [
            'workshop_id' => $firstWorkshop->id,
            'customer_id' => null,
            'customer_phone_normalized' => '+380501112233',
        ]);
        $this->assertDatabaseHas('booking_requests', [
            'workshop_id' => $secondWorkshop->id,
            'customer_id' => null,
            'customer_phone_normalized' => '+380501112233',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
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
