<?php

namespace Tests\Feature;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Workshop;
use App\Support\Intake\IntakeExtractionResult;
use App\Support\Intake\IntakeExtractorInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicIntakeSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_is_not_a_workshop_less_intake_form(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
                ->missing('intakeSubmitted')
                ->missing('workshops'));
    }

    public function test_guest_can_open_workshop_public_intake_page(): void
    {
        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        $this->get('/w/main-auto')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicIntake')
                ->where('workshop.name', 'Main Auto')
                ->where('workshop.slug', 'main-auto')
                ->where('intakeSubmitted', false)
                ->missing('workshops'));
    }

    public function test_guest_can_submit_natural_message_to_route_workshop_without_account(): void
    {
        $workshop = Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);
        $message = 'Opel Insignia, check engine light came on, maybe sensors, when can I come?';
        $phone = '+38 (050) 111-22-33';

        $response = $this->post('/w/main-auto/intake', [
            'message' => $message,
            'phone' => $phone,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('intake_submitted', true)
            ->assertRedirect('/w/main-auto');

        $bookingRequest = BookingRequest::first();

        $this->assertGuest();
        $this->assertDatabaseCount('booking_requests', 1);
        $this->assertDatabaseCount('repair_orders', 0);
        $this->assertNotNull($bookingRequest);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertSame('380501112233', $bookingRequest->customer_phone);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame($message, $bookingRequest->problem_description);
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
    }

    public function test_public_intake_succeeds_with_message_and_phone_only(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->post('/w/main-auto/intake', [
            'message' => 'Need help with a check engine light.',
            'phone' => '+1 (555) 123-4567',
        ])->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::sole();

        $this->assertSame('Need help with a check engine light.', $bookingRequest->original_message);
        $this->assertSame('15551234567', $bookingRequest->customer_phone);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertNull($bookingRequest->preferred_date);
    }

    public function test_public_intake_fails_without_phone(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->from('/w/main-auto')->post('/w/main-auto/intake', [
            'message' => 'Need help with a check engine light.',
        ])
            ->assertSessionHasErrors('phone')
            ->assertRedirect('/w/main-auto');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_blank_public_intake_message_is_rejected_without_creating_booking_request(): void
    {
        $workshop = Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $response = $this->from('/w/main-auto')->post('/w/main-auto/intake', [
            'message' => '     ',
            'phone' => '+1 (555) 123-4567',
        ]);

        $response
            ->assertSessionHasErrors('message')
            ->assertRedirect('/w/main-auto');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_public_intake_stores_original_message_unchanged_with_new_status(): void
    {
        $workshop = Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);
        $message = '  Opel Insignia, check engine light came on. Call +38 (050) 111-22-33 please.  ';

        $this->post('/w/main-auto/intake', [
            'message' => $message,
            'phone' => '+38 (050) 111-22-33',
        ])->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::first();

        $this->assertNotNull($bookingRequest);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertSame('380501112233', $bookingRequest->customer_phone);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame(trim($message), $bookingRequest->problem_description);
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
    }

    public function test_intake_action_patches_optional_summary_without_creating_customer_vehicle_or_repair_order(): void
    {
        $workshop = Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);
        $message = 'Honda Civic makes noise. Call +1 (555) 123-4567.';

        $this->app->bind(IntakeExtractorInterface::class, fn () => new class implements IntakeExtractorInterface
        {
            public function extract(string $message): IntakeExtractionResult
            {
                return new IntakeExtractionResult(
                    phone: '15551234567',
                    vehicleMake: 'Honda',
                    vehicleModel: 'Civic',
                    vehiclePlate: 'ABC123',
                    preferredTimeText: 'Tomorrow',
                    problemSummary: 'Honda Civic makes noise.',
                    missingNextField: null,
                    confidence: 0.9,
                );
            }
        });

        $this->post('/w/main-auto/intake', [
            'message' => $message,
            'phone' => '+1 (555) 765-4321',
        ])->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::first();

        $this->assertNotNull($bookingRequest);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertNull($bookingRequest->created_by_user_id);
        $this->assertSame('15557654321', $bookingRequest->customer_phone);
        $this->assertSame('Honda Civic makes noise.', $bookingRequest->problem_description);
        $this->assertNull($bookingRequest->preferred_date);
        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_public_intake_creates_booking_request_before_optional_enrichment_can_fail(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->app->bind(IntakeExtractorInterface::class, fn () => new class implements IntakeExtractorInterface
        {
            public function extract(string $message): IntakeExtractionResult
            {
                throw new \RuntimeException('OpenAI unavailable');
            }
        });

        $message = 'Mazda needs service next week.';

        $this->post('/w/main-auto/intake', [
            'message' => $message,
            'phone' => '+1 (555) 123-4567',
        ])->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::sole();

        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame($message, $bookingRequest->problem_description);
        $this->assertSame('15551234567', $bookingRequest->customer_phone);
    }

    public function test_public_intake_never_creates_unassigned_booking_request(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->post('/w/main-auto/intake', [
            'message' => 'Toyota Corolla needs oil service next week.',
            'phone' => '+1 (555) 123-4567',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, BookingRequest::query()->whereNull('workshop_id')->count());
    }

    public function test_public_intake_response_supports_inertia_submitted_state(): void
    {
        Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);

        $this->post('/w/main-auto/intake', [
            'message' => 'Toyota Corolla needs oil service next week.',
            'phone' => '+1 (555) 123-4567',
        ])->assertRedirect('/w/main-auto');

        $this->get('/w/main-auto')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PublicIntake')
                ->where('workshop.name', 'Main Auto')
                ->where('intakeSubmitted', true));
    }

    public function test_filled_honeypot_field_rejects_submission_without_creating_booking_request(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $response = $this->from('/w/main-auto')->post('/w/main-auto/intake', [
            'message' => 'Toyota Corolla needs oil service next week.',
            'phone' => '+1 (555) 123-4567',
            'website' => 'https://spam.example',
        ]);

        $response
            ->assertSessionHasErrors('website')
            ->assertRedirect('/w/main-auto');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_empty_honeypot_field_does_not_block_submission(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        $this->post('/w/main-auto/intake', [
            'message' => 'Toyota Corolla needs oil service next week.',
            'phone' => '+1 (555) 123-4567',
            'website' => '',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('booking_requests', 1);
    }

    public function test_public_intake_submission_is_rate_limited(): void
    {
        Workshop::factory()->create([
            'slug' => 'main-auto',
        ]);

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->post('/w/main-auto/intake', [
                'message' => "Toyota Corolla needs oil service, attempt {$attempt}.",
                'phone' => '+1 (555) 123-4567',
            ])->assertRedirect('/w/main-auto');
        }

        $this->post('/w/main-auto/intake', [
            'message' => 'Toyota Corolla needs oil service, attempt 11.',
            'phone' => '+1 (555) 123-4567',
        ])->assertStatus(429);

        $this->assertDatabaseCount('booking_requests', 10);
    }

    public function test_booking_requests_do_not_store_diagnosis_or_recommendation_fields(): void
    {
        $columns = Schema::getColumnListing('booking_requests');

        $this->assertNotContains('diagnosis', $columns);
        $this->assertNotContains('diagnosis_notes', $columns);
        $this->assertNotContains('recommendation', $columns);
        $this->assertNotContains('recommended_repairs', $columns);
        $this->assertNotContains('estimated_price', $columns);
    }
}
