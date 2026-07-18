<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Models\BookingRequest;
use App\Models\Workshop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicIntakeSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_contains_global_intake_with_available_workshops(): void
    {
        $second = Workshop::factory()->create(['name' => 'Second Auto']);
        $first = Workshop::factory()->create(['name' => 'Alpha Auto']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('intakeSubmitted', false)
                ->where('workshops', [
                    ['id' => $first->id, 'name' => 'Alpha Auto'],
                    ['id' => $second->id, 'name' => 'Second Auto'],
                ]));

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_homepage_exposes_verified_customer_history_without_phone_data(): void
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

        $this->withSession([
            'customer_portal.verified_phone' => '+380501112233',
            'customer_portal.verified_until' => now()->addMinutes(10)->timestamp,
        ])->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('recentRequests', 1)
                ->where('recentRequests.0.id', $owned->id)
                ->where('recentRequests.0.title', 'Brake noise')
                ->where('recentRequests.0.workshopName', 'Main Auto')
                ->where('hasMoreRequests', false)
                ->missing('verifiedPhone')
                ->missing('phone'));
    }

    public function test_homepage_omits_verified_customer_history_for_an_expired_session(): void
    {
        BookingRequest::factory()->create([
            'customer_phone' => '+380501112233',
            'problem_description' => 'Brake noise',
        ]);

        $this->withSession([
            'customer_portal.verified_phone' => '+380501112233',
            'customer_portal.verified_until' => now()->subMinute()->timestamp,
        ])->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->missing('recentRequests')
                ->missing('hasMoreRequests'));
    }

    public function test_workshop_is_required_on_final_submission(): void
    {
        Workshop::factory()->create();

        $this->from('/')->post('/intake', $this->validPayload(['workshop_id' => null]))
            ->assertSessionHasErrors('workshop_id')
            ->assertRedirect('/');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_invalid_workshop_is_rejected(): void
    {
        $this->from('/')->post('/intake', $this->validPayload(['workshop_id' => 999999]))
            ->assertSessionHasErrors('workshop_id')
            ->assertRedirect('/');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_selected_workshop_receives_booking_request_with_vehicle_snapshots(): void
    {
        $otherWorkshop = Workshop::factory()->create();
        $selectedWorkshop = Workshop::factory()->create();
        $message = 'Opel Insignia, check engine light came on.';
        $phone = '+38 (050) 111-22-33';

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $selectedWorkshop->id,
            'message' => $message,
            'phone' => $phone,
            'vehicle' => [
                'brand' => ' Opel ',
                'model' => ' Insignia ',
                'year' => 2018,
                'license_plate' => ' AA 1234 BB ',
            ],
        ]))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('intake_submitted', true)
            ->assertRedirect('/');

        $bookingRequest = BookingRequest::query()->sole();

        $this->assertGuest();
        $this->assertSame($selectedWorkshop->id, $bookingRequest->workshop_id);
        $this->assertNotSame($otherWorkshop->id, $bookingRequest->workshop_id);
        $this->assertNull($bookingRequest->customer_id);
        $this->assertNull($bookingRequest->vehicle_id);
        $this->assertNull($bookingRequest->created_by_user_id);
        $this->assertSame($phone, $bookingRequest->customer_phone);
        $this->assertSame('+380501112233', $bookingRequest->customer_phone_normalized);
        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame($message, $bookingRequest->problem_description);
        $this->assertSame('Opel', $bookingRequest->vehicle_brand);
        $this->assertSame('Insignia', $bookingRequest->vehicle_model);
        $this->assertSame(2018, $bookingRequest->vehicle_year);
        $this->assertSame('AA 1234 BB', $bookingRequest->vehicle_license_plate);
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
    }

    public function test_original_message_preserves_customer_whitespace_exactly(): void
    {
        $workshop = Workshop::factory()->create();
        $message = '  Opel Insignia, check engine light came on.  ';

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'message' => $message,
        ]))->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::query()->sole();

        $this->assertSame($message, $bookingRequest->original_message);
        $this->assertSame(trim($message), $bookingRequest->problem_description);
    }

    public function test_public_intake_never_creates_unassigned_booking_request(): void
    {
        $workshop = Workshop::factory()->create();

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
        ]))->assertSessionHasNoErrors();

        $this->assertSame(0, BookingRequest::query()->whereNull('workshop_id')->count());
    }

    public function test_public_intake_does_not_create_related_domain_records(): void
    {
        $workshop = Workshop::factory()->create();

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('vehicles', 0);
        $this->assertDatabaseCount('repair_orders', 0);
    }

    public function test_booking_request_workshop_column_is_not_nullable(): void
    {
        $isNullable = DB::table('information_schema.columns')
            ->where('table_schema', 'public')
            ->where('table_name', 'booking_requests')
            ->where('column_name', 'workshop_id')
            ->value('is_nullable');

        $this->assertSame('NO', $isNullable);
    }

    public function test_snapshot_migration_refuses_legacy_unassigned_booking_requests(): void
    {
        DB::statement('ALTER TABLE booking_requests ALTER COLUMN workshop_id DROP NOT NULL');
        DB::table('booking_requests')->insert([
            'workshop_id' => null,
            'customer_id' => null,
            'vehicle_id' => null,
            'created_by_user_id' => null,
            'customer_name' => null,
            'customer_phone' => '+1 (555) 123-4567',
            'customer_phone_normalized' => '+15551234567',
            'problem_description' => 'Legacy unassigned request.',
            'original_message' => 'Legacy unassigned request.',
            'preferred_date' => null,
            'status' => BookingRequestStatus::New->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path(
            'migrations/2026_07_13_000002_add_vehicle_snapshots_to_booking_requests_table.php'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Cannot enforce booking_requests.workshop_id NOT NULL because legacy unassigned booking requests exist.'
        );

        $migration->up();
    }

    public function test_vehicle_details_are_optional(): void
    {
        $workshop = Workshop::factory()->create();

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'vehicle' => [],
        ]))->assertSessionHasNoErrors();

        $bookingRequest = BookingRequest::query()->sole();

        $this->assertNull($bookingRequest->vehicle_brand);
        $this->assertNull($bookingRequest->vehicle_model);
        $this->assertNull($bookingRequest->vehicle_year);
        $this->assertNull($bookingRequest->vehicle_license_plate);
    }

    public function test_malformed_vehicle_payload_is_rejected(): void
    {
        $workshop = Workshop::factory()->create();

        $this->from('/')->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'vehicle' => 'Opel Insignia',
        ]))
            ->assertSessionHasErrors('vehicle')
            ->assertRedirect('/');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_blank_message_and_invalid_phone_are_rejected(): void
    {
        $workshop = Workshop::factory()->create();

        $this->from('/')->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'message' => '   ',
            'phone' => '123',
        ]))
            ->assertSessionHasErrors(['message', 'phone'])
            ->assertRedirect('/');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_honeypot_rejects_submission(): void
    {
        $workshop = Workshop::factory()->create();

        $this->from('/')->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'website' => 'https://spam.example',
        ]))
            ->assertSessionHasErrors('website')
            ->assertRedirect('/');

        $this->assertDatabaseCount('booking_requests', 0);
    }

    public function test_empty_honeypot_is_accepted_and_success_state_is_exposed(): void
    {
        $workshop = Workshop::factory()->create();

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'website' => '',
        ]))->assertRedirect('/');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('intakeSubmitted', true));
    }

    public function test_workshop_specific_and_legacy_booking_routes_are_removed(): void
    {
        $workshop = Workshop::factory()->create(['slug' => 'main-auto']);

        $this->get('/w/main-auto')->assertNotFound();
        $this->post('/w/main-auto/intake', $this->validPayload(['workshop_id' => $workshop->id]))
            ->assertNotFound();
        $this->get('/book/main-auto')->assertNotFound();
        $this->post('/book/main-auto', [])->assertNotFound();
    }

    public function test_public_intake_submission_is_rate_limited(): void
    {
        $workshop = Workshop::factory()->create();

        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->post('/intake', $this->validPayload([
                'workshop_id' => $workshop->id,
                'message' => "Toyota Corolla needs oil service, attempt {$attempt}.",
            ]))->assertRedirect('/');
        }

        $this->post('/intake', $this->validPayload([
            'workshop_id' => $workshop->id,
            'message' => 'Toyota Corolla needs oil service, attempt 11.',
        ]))->assertStatus(429);

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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'message' => 'Need help with a check engine light.',
            'phone' => '+1 (555) 123-4567',
            'workshop_id' => null,
            'vehicle' => [
                'brand' => null,
                'model' => null,
                'year' => null,
                'license_plate' => null,
            ],
            'website' => '',
        ], $overrides);
    }
}
