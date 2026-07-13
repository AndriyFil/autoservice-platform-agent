<?php

namespace Tests\Feature;

use App\Domain\BookingRequests\Enums\BookingRequestStatus;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Estimates\Enums\EstimateStatus;
use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Models\BookingRequest;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_intake_to_repair_order_estimate_document_admin_workflow(): void
    {
        Storage::fake('documents_local');

        $workshop = Workshop::factory()->create([
            'name' => 'Main Auto',
            'slug' => 'main-auto',
        ]);
        $otherWorkshop = Workshop::factory()->create([
            'name' => 'Other Auto',
            'slug' => 'other-auto',
        ]);
        $staff = User::factory()->create();
        $otherStaff = User::factory()->create();
        $this->createMembership($staff, $workshop, WorkshopUserRole::Staff);
        $this->createMembership($otherStaff, $otherWorkshop, WorkshopUserRole::Staff);

        $customerMessage = 'Opel Insignia, check engine light came on, maybe sensors, when can I come?';
        $customerPhone = '+38 (050) 111-22-33';

        $this
            ->post(route('public-intake.store', $workshop), [
                'message' => $customerMessage,
                'phone' => $customerPhone,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('public-intake.create', $workshop));

        $bookingRequest = BookingRequest::query()->sole();

        $this->assertSame($workshop->id, $bookingRequest->workshop_id);
        $this->assertSame($customerMessage, $bookingRequest->original_message);
        $this->assertSame($customerMessage, $bookingRequest->problem_description);
        $this->assertSame($customerPhone, $bookingRequest->customer_phone);
        $this->assertSame('+380501112233', $bookingRequest->customer_phone_normalized);
        $this->assertSame(BookingRequestStatus::New, $bookingRequest->status);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('repair_orders', 0);

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/BookingRequests/Show')
                ->where('activeWorkshop.id', $workshop->id)
                ->where('bookingRequest.id', $bookingRequest->id)
                ->where('bookingRequest.originalMessage', $customerMessage)
                ->where('canCreateRepairOrder', true)
                ->where('matchedCustomer', null));

        $this
            ->actingAs($otherStaff)
            ->withSession(['active_workshop_id' => $otherWorkshop->id])
            ->get(route('dashboard.booking-requests.show', $bookingRequest))
            ->assertNotFound();

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.create', [
                'booking_request' => $bookingRequest->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/RepairOrders/Create')
                ->where('defaults.booking_request_id', (string) $bookingRequest->id)
                ->where('defaults.problem_description', $customerMessage)
                ->where('defaults.customer_phone', $customerPhone)
                ->where('sourceBookingRequest.id', $bookingRequest->id)
                ->where('sourceBookingRequest.existingCustomer', null));

        $this->assertSame(BookingRequestStatus::Confirmed, $bookingRequest->refresh()->status);

        $internalProblemDescription = 'Internal inspection: check engine light, scan sensors, confirm with customer.';

        $createRepairOrderResponse = $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.store'), [
                'booking_request_id' => $bookingRequest->id,
                'problem_description' => $internalProblemDescription,
                'requires_estimate_approval' => true,
            ]);

        $repairOrder = RepairOrder::query()->sole();
        $customer = Customer::query()->sole();

        $createRepairOrderResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $this->assertSame($workshop->id, $repairOrder->workshop_id);
        $this->assertSame($bookingRequest->id, $repairOrder->booking_request_id);
        $this->assertSame($customer->id, $repairOrder->customer_id);
        $this->assertNull($repairOrder->vehicle_id);
        $this->assertSame($staff->id, $repairOrder->created_by_user_id);
        $this->assertSame(RepairOrderStatus::Draft, $repairOrder->status);
        $this->assertSame($internalProblemDescription, $repairOrder->problem_description);
        $this->assertSame($workshop->id, $customer->workshop_id);
        $this->assertNull($customer->name);
        $this->assertSame($customerPhone, $customer->phone);
        $this->assertSame('+380501112233', $customer->phone_normalized);
        $this->assertDatabaseCount('users', 2);

        $this
            ->actingAs($otherStaff)
            ->withSession(['active_workshop_id' => $otherWorkshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertNotFound();

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.lines.store', $repairOrder), [
                'type' => RepairOrderLineType::Labor->value,
                'description' => 'Diagnostic scan and sensor inspection',
                'quantity' => '2.00',
                'unit_price_cents' => 10000,
                'tax_rate' => '20.00',
                'sort_order' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $line = $repairOrder->lines()->sole();
        $repairOrder->refresh()->load('lines');

        $this->assertSame(20000, $line->subtotalCents());
        $this->assertSame(4000, $line->taxCents());
        $this->assertSame(24000, $line->totalCents());
        $this->assertSame(20000, $repairOrder->subtotalCents());
        $this->assertSame(4000, $repairOrder->taxCents());
        $this->assertSame(24000, $repairOrder->totalCents());

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('repairOrder.problemDescription', $internalProblemDescription)
                ->where('repairOrder.bookingRequest.id', $bookingRequest->id)
                ->where('repairOrder.lines.0.subtotalCents', 20000)
                ->where('repairOrder.lines.0.taxCents', 4000)
                ->where('repairOrder.lines.0.totalCents', 24000)
                ->where('repairOrder.workingTotals.subtotalCents', 20000)
                ->where('repairOrder.workingTotals.taxCents', 4000)
                ->where('repairOrder.workingTotals.totalCents', 24000)
                ->where('repairOrder.statusActions.canGenerateEstimate', true));

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder));

        $estimate = Estimate::query()->with('lines')->sole();
        $document = Document::query()->sole();

        $this->assertSame($repairOrder->id, $estimate->repair_order_id);
        $this->assertSame(EstimateStatus::Generated, $estimate->status);
        $this->assertSame(1, $estimate->version);
        $this->assertSame(20000, $estimate->subtotal_cents);
        $this->assertSame(4000, $estimate->tax_cents);
        $this->assertSame(24000, $estimate->total_cents);
        $this->assertSame('Diagnostic scan and sensor inspection', $estimate->lines->sole()->description);
        $this->assertSame($workshop->id, $document->workshop_id);
        $this->assertSame(Estimate::class, $document->documentable_type);
        $this->assertSame($estimate->id, $document->documentable_id);
        $this->assertSame(DocumentType::EstimatePdf, $document->type);
        $this->assertSame(DocumentStatus::Generated, $document->status);
        $this->assertSame('documents_local', $document->disk);
        $this->assertSame('application/pdf', $document->mime_type);
        Storage::disk('documents_local')->assertExists($document->path);

        $this
            ->actingAs($staff)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.documents.download', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this
            ->actingAs($otherStaff)
            ->withSession(['active_workshop_id' => $otherWorkshop->id])
            ->get(route('dashboard.documents.download', $document))
            ->assertNotFound();
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
