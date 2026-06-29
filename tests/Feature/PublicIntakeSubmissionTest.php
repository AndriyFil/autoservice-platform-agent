<?php

namespace Tests\Feature;

use App\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Support\Intake\IntakeExtractionResult;
use App\Support\Intake\IntakeExtractorInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicIntakeSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_does_not_expose_workshop_props(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('canLogin')
                ->has('canRegister')
                ->has('intakeSubmitted')
                ->missing('workshops'));
    }

    public function test_guest_can_submit_natural_message_without_account(): void
    {
        $message = 'Opel Insignia, check engine light came on, maybe sensors, when can I come?';

        $response = $this->post('/intake', [
            'message' => $message,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertSessionHas('intake_submitted', true)
            ->assertRedirect('/');

        $bookingRequest = BookingRequest::first();

        $this->assertGuest();
        $this->assertDatabaseCount('booking_requests', 1);
        $this->assertDatabaseCount('repair_orders', 0);
        $this->assertNotNull($bookingRequest);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame($message, $bookingRequest->problem_description);
        $this->assertSame(BookingRequestStatus::Submitted, $bookingRequest->status);
    }

    public function test_blank_public_intake_message_is_rejected_without_creating_booking_request(): void
    {
        $response = $this->from('/')->post('/intake', [
            'message' => '     ',
        ]);

        $response
            ->assertSessionHasErrors('message')
            ->assertRedirect('/');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_public_intake_stores_original_message_unchanged_with_submitted_status(): void
    {
        $message = '  Opel Insignia, check engine light came on. Call +38 (050) 111-22-33 please.  ';

        $this->post('/intake', [
            'message' => $message,
        ])->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::first();

        $this->assertNotNull($bookingRequest);
        $this->assertNull($bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertSame('380501112233', $bookingRequest->customer_phone);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame($message, $bookingRequest->problem_description);
        $this->assertSame(BookingRequestStatus::Submitted, $bookingRequest->status);
    }

    public function test_intake_action_persists_safe_phone_without_creating_customer_or_vehicle(): void
    {
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

        $this->post('/intake', [
            'message' => $message,
        ])->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::first();

        $this->assertNotNull($bookingRequest);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertNull($bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertNull($bookingRequest->created_by_user_id);
        $this->assertSame('15551234567', $bookingRequest->customer_phone);
        $this->assertSame($message, $bookingRequest->problem_description);
        $this->assertNull($bookingRequest->preferred_date);
    }

    public function test_public_intake_response_supports_inertia_submitted_state(): void
    {
        $this->post('/intake', [
            'message' => 'Toyota Corolla needs oil service next week.',
        ])->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('intakeSubmitted', true));
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
