<?php

namespace Tests\Feature;

use App\Actions\Estimates\GenerateRepairOrderEstimateAction;
use App\Actions\Estimates\GenerateRepairOrderEstimateResult;
use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class EstimateDocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_estimate_copies_current_repair_order_lines_into_immutable_snapshot(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        $line = RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Initial diagnostic labor',
            'quantity' => '2.00',
            'unit_price_cents' => 10000,
            'tax_rate' => '20.00',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', __('repair_orders.estimate_created'));

        $estimate = Estimate::query()->with('lines')->sole();

        $this->assertSame(1, $estimate->version);
        $this->assertSame(20000, $estimate->subtotal_cents);
        $this->assertSame(4000, $estimate->tax_cents);
        $this->assertSame(24000, $estimate->total_cents);
        $this->assertSame('Initial diagnostic labor', $estimate->lines->sole()->description);
        $this->assertSame(20000, $estimate->lines->sole()->subtotal_cents);
        $this->assertSame(4000, $estimate->lines->sole()->tax_cents);
        $this->assertSame(24000, $estimate->lines->sole()->total_cents);

        $line->update([
            'description' => 'Changed working line',
            'quantity' => '1.00',
            'unit_price_cents' => 5000,
        ]);

        $this->assertSame('Initial diagnostic labor', $estimate->lines->sole()->refresh()->description);
        $this->assertSame(24000, $estimate->lines->sole()->refresh()->total_cents);
    }

    public function test_posting_estimate_endpoint_again_creates_next_estimate_version(): void
    {
        Storage::fake('documents_local');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        $line = RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'description' => 'First version line',
            'quantity' => '1.00',
            'unit_price_cents' => 10000,
            'tax_rate' => '0.00',
        ]);

        $this->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder));

        $oldDocument = Document::query()->sole();

        $line->update([
            'description' => 'Second version line',
            'unit_price_cents' => 12000,
        ]);

        $this->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder));

        $estimates = Estimate::query()->with('lines')->orderBy('version')->get();
        $documents = Document::query()->orderBy('id')->get();

        $this->assertCount(2, $estimates);
        $this->assertSame(1, $estimates[0]->version);
        $this->assertSame(2, $estimates[1]->version);
        $this->assertSame('First version line', $estimates[0]->lines->sole()->description);
        $this->assertSame(10000, $estimates[0]->lines->sole()->total_cents);
        $this->assertSame('Second version line', $estimates[1]->lines->sole()->description);
        $this->assertSame(12000, $estimates[1]->lines->sole()->total_cents);

        $this->assertCount(2, $documents);
        $this->assertSame(DocumentStatus::Generated, $oldDocument->refresh()->status);
        $this->assertSame(DocumentStatus::Generated, $documents[1]->status);
    }

    public function test_generated_pdf_creates_private_document_with_storage_metadata_and_workshop_scope(): void
    {
        Storage::fake('documents_local');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'description' => 'Estimate PDF line',
            'quantity' => '1.00',
            'unit_price_cents' => 15000,
            'tax_rate' => '10.00',
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHasNoErrors();

        $document = Document::query()->sole();

        $this->assertSame('estimate_pdf', $document->type->value);
        $this->assertSame($workshop->id, $document->workshop_id);
        $this->assertSame('documents_local', $document->disk);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertNotNull($document->size_bytes);
        $this->assertGreaterThan(0, $document->size_bytes);
        $this->assertNotNull($document->checksum_sha256);
        $this->assertSame(64, strlen($document->checksum_sha256));
        $this->assertInstanceOf(Estimate::class, $document->documentable);
        Storage::disk('documents_local')->assertExists($document->path);
    }

    public function test_estimate_route_delegates_to_single_high_level_action(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $workshopUser = $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'status' => 'generated',
        ]);
        $document = Document::factory()->create([
            'workshop_id' => $workshop->id,
            'documentable_type' => Estimate::class,
            'documentable_id' => $estimate->id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
        ]);

        $action = Mockery::mock(GenerateRepairOrderEstimateAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->withArgs(fn (WorkshopUser $activeWorkshopUser, RepairOrder $routedRepairOrder): bool => (
                $activeWorkshopUser->is($workshopUser)
                && $routedRepairOrder->is($repairOrder)
            ))
            ->andReturn(new GenerateRepairOrderEstimateResult($document));

        $this->instance(GenerateRepairOrderEstimateAction::class, $action);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', __('repair_orders.estimate_created'));
    }

    public function test_creating_estimate_uses_localized_status_flash_message(): void
    {
        app()->setLocale('uk');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'description' => 'Localized estimate line',
            'unit_price_cents' => 15000,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', __('repair_orders.estimate_created'));
    }

    public function test_same_workshop_can_download_document_and_another_workshop_cannot(): void
    {
        Storage::fake('documents_local');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $otherWorkshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $this->createMembership($user, $otherWorkshop);
        $repairOrder = $this->createRepairOrder($workshop);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'description' => 'Downloadable estimate line',
            'unit_price_cents' => 15000,
        ]);

        $this->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->post(route('dashboard.repair-orders.estimate', $repairOrder));

        $document = Document::query()->sole();

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.documents.download', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $otherWorkshop->id])
            ->get(route('dashboard.documents.download', $document))
            ->assertNotFound();
    }

    public function test_single_estimate_route_keeps_old_document_and_creates_next_version(): void
    {
        Storage::fake('documents_local');

        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop, [
            'status' => 'draft',
        ]);
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'description' => 'Current route second version line',
            'quantity' => '1.00',
            'unit_price_cents' => 17000,
            'tax_rate' => '0.00',
        ]);
        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'status' => 'generated',
            'subtotal_cents' => 9000,
            'tax_cents' => 0,
            'total_cents' => 9000,
            'generated_at' => now()->subDay(),
        ]);
        $oldDocument = $estimate->documents()->create([
            'workshop_id' => $workshop->id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => 'documents_local',
            'path' => 'workshops/'.$workshop->id.'/estimates/'.$estimate->id.'/old.pdf',
            'filename' => 'old.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'old'),
            'generated_at' => now()->subDay(),
            'created_by_user_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->from(route('dashboard.repair-orders.show', $repairOrder))
            ->post(route('dashboard.repair-orders.estimate', $repairOrder))
            ->assertRedirect(route('dashboard.repair-orders.show', $repairOrder))
            ->assertSessionHas('status', __('repair_orders.estimate_created'));

        $estimates = Estimate::query()->with('lines')->orderBy('version')->get();

        $this->assertSame(DocumentStatus::Generated, $oldDocument->refresh()->status);
        $this->assertSame(1, $estimate->refresh()->generatedEstimatePdfDocuments()->count());
        $this->assertCount(2, $estimates);
        $this->assertSame(1, $estimates[0]->version);
        $this->assertSame(2, $estimates[1]->version);
        $this->assertSame('Current route second version line', $estimates[1]->lines()->sole()->description);
    }

    public function test_dashboard_details_exposes_only_latest_generated_estimate_pdf(): void
    {
        $user = User::factory()->create();
        $workshop = Workshop::factory()->create();
        $this->createMembership($user, $workshop);
        $repairOrder = $this->createRepairOrder($workshop);
        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'status' => 'generated',
            'generated_at' => now(),
        ]);
        $estimate->documents()->create([
            'workshop_id' => $workshop->id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Archived,
            'disk' => 'documents_local',
            'path' => 'archived.pdf',
            'filename' => 'archived.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'checksum_sha256' => hash('sha256', 'archived'),
            'generated_at' => now()->subMinutes(3),
            'created_by_user_id' => null,
        ]);
        $estimate->documents()->create([
            'workshop_id' => $workshop->id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Failed,
            'disk' => 'documents_local',
            'path' => 'failed.pdf',
            'filename' => 'failed.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 0,
            'checksum_sha256' => hash('sha256', 'failed'),
            'generated_at' => now()->subMinutes(2),
            'created_by_user_id' => null,
        ]);
        $generatedDocument = $estimate->documents()->create([
            'workshop_id' => $workshop->id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => 'documents_local',
            'path' => 'generated.pdf',
            'filename' => 'generated.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 200,
            'checksum_sha256' => hash('sha256', 'generated'),
            'generated_at' => now()->subMinute(),
            'created_by_user_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->withSession(['active_workshop_id' => $workshop->id])
            ->get(route('dashboard.repair-orders.show', $repairOrder))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('repairOrder.estimates.0.document.id', $generatedDocument->id)
                ->where('repairOrder.estimates.0.document.filename', 'generated.pdf'));
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

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRepairOrder(Workshop $workshop, array $overrides = []): RepairOrder
    {
        $customer = Customer::create([
            'workshop_id' => $workshop->id,
            'name' => 'Jane Driver',
            'phone' => '+1 555 123 4567',
            'normalized_phone' => '15551234567',
        ]);

        return RepairOrder::create(array_merge([
            'workshop_id' => $workshop->id,
            'customer_id' => $customer->id,
            'vehicle_id' => null,
            'booking_request_id' => null,
            'status' => 'draft',
            'problem_description' => 'Brake noise on cold start.',
            'opened_at' => now(),
            'closed_at' => null,
        ], $overrides));
    }
}
