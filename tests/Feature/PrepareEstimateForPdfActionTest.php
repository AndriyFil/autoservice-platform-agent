<?php

namespace Tests\Feature;

use App\Actions\Estimates\PrepareEstimateForPdfAction;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\EstimateStatus;
use App\Enums\RepairOrderLineType;
use App\Enums\RepairOrderStatus;
use App\Enums\WorkshopUserRole;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrepareEstimateForPdfActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_repair_order_with_lines_creates_first_estimate_and_becomes_estimated(): void
    {
        [$workshopUser, $repairOrder] = $this->buildRepairOrderWithLine();

        $estimate = app(PrepareEstimateForPdfAction::class)->handle($workshopUser, $repairOrder);

        $this->assertSame(EstimateStatus::Generated, $estimate->status);
        $this->assertNotNull($estimate->generated_at);
        $this->assertSame(RepairOrderStatus::Estimated, $repairOrder->refresh()->status);
        $this->assertSame(1, $estimate->version);
        $this->assertSame('Original repair order line', $estimate->lines->sole()->description);
        $this->assertSame(9000, $estimate->total_cents);
    }

    public function test_generated_current_estimate_is_rebuilt_before_approval(): void
    {
        Storage::fake('documents_local');

        [$workshopUser, $repairOrder, $estimate, $oldDocument] = $this->buildRegenerableEstimate();
        Storage::disk('documents_local')->put($oldDocument->path, 'old pdf contents');
        $generatedAt = $estimate->generated_at;

        $repairOrder->lines()->delete();
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Current repair order line',
            'quantity' => '2.00',
            'unit_price_cents' => 20000,
            'tax_rate' => '0.00',
            'sort_order' => 1,
        ]);

        $preparedEstimate = app(PrepareEstimateForPdfAction::class)->handle($workshopUser, $repairOrder);

        $oldDocument->refresh();

        $this->assertSame($estimate->id, $preparedEstimate->id);
        $this->assertSame(DocumentStatus::Archived, $oldDocument->status);
        $this->assertTrue(Document::query()->whereKey($oldDocument->id)->exists());
        Storage::disk('documents_local')->assertExists($oldDocument->path);
        $this->assertSame(0, $estimate->refresh()->generatedEstimatePdfDocuments()->count());

        $line = $preparedEstimate->lines->sole();
        $this->assertSame('Current repair order line', $line->description);
        $this->assertSame(40000, $line->subtotal_cents);
        $this->assertSame(40000, $line->total_cents);

        $this->assertSame(40000, $preparedEstimate->subtotal_cents);
        $this->assertSame(0, $preparedEstimate->tax_cents);
        $this->assertSame(40000, $preparedEstimate->total_cents);
        $this->assertSame(EstimateStatus::Generated, $preparedEstimate->status);
        $this->assertTrue($preparedEstimate->generated_at->greaterThan($generatedAt));
    }

    public function test_failed_documents_are_kept_failed_when_regenerating(): void
    {
        [$workshopUser, $repairOrder, $estimate] = $this->buildRegenerableEstimate();
        $failedDocument = $estimate->documents()->create([
            'workshop_id' => $repairOrder->workshop_id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Failed,
            'disk' => 'documents_local',
            'path' => 'workshops/'.$repairOrder->workshop_id.'/estimates/'.$estimate->id.'/failed.pdf',
            'filename' => 'failed.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 0,
            'checksum_sha256' => hash('sha256', 'failed'),
            'generated_at' => now()->subHour(),
            'created_by_user_id' => null,
        ]);

        app(PrepareEstimateForPdfAction::class)->handle($workshopUser, $repairOrder);

        $this->assertSame(DocumentStatus::Failed, $failedDocument->refresh()->status);
    }

    public function test_approved_estimate_cannot_be_prepared_for_regeneration(): void
    {
        [$workshopUser, $repairOrder, $estimate] = $this->buildRegenerableEstimate(
            estimateStatus: EstimateStatus::Approved,
        );

        $documentsBefore = Document::query()->count();

        try {
            app(PrepareEstimateForPdfAction::class)->handle($workshopUser, $repairOrder);
            $this->fail('Expected DomainException for approved estimate.');
        } catch (DomainException) {
            // expected
        }

        $this->assertSame($documentsBefore, Document::query()->count());
        $this->assertSame(EstimateStatus::Approved, $estimate->refresh()->status);
    }

    public function test_approved_repair_order_cannot_be_prepared_for_regeneration(): void
    {
        [$workshopUser, $repairOrder] = $this->buildRegenerableEstimate(
            repairOrderStatus: RepairOrderStatus::Approved,
        );

        $documentsBefore = Document::query()->count();

        try {
            app(PrepareEstimateForPdfAction::class)->handle($workshopUser, $repairOrder);
            $this->fail('Expected DomainException for approved repair order.');
        } catch (DomainException) {
            // expected
        }

        $this->assertSame($documentsBefore, Document::query()->count());
    }

    public function test_other_workshop_cannot_prepare_estimate_by_guessing_repair_order_id(): void
    {
        [, $repairOrder] = $this->buildRepairOrderWithLine();
        $otherWorkshop = Workshop::factory()->create();
        $otherUser = User::factory()->create();
        $otherWorkshopUser = $this->createMembership($otherUser, $otherWorkshop);

        $this->expectException(ModelNotFoundException::class);

        app(PrepareEstimateForPdfAction::class)->handle($otherWorkshopUser, $repairOrder);
    }

    /**
     * @return array{0: WorkshopUser, 1: RepairOrder}
     */
    private function buildRepairOrderWithLine(RepairOrderStatus $repairOrderStatus = RepairOrderStatus::Draft): array
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create();
        $workshopUser = $this->createMembership($user, $workshop);
        $repairOrder = RepairOrder::factory()->forWorkshop($workshop)->create([
            'status' => $repairOrderStatus,
        ]);

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Original repair order line',
            'quantity' => '1.00',
            'unit_price_cents' => 9000,
            'tax_rate' => '0.00',
            'sort_order' => 1,
        ]);

        return [$workshopUser, $repairOrder->refresh()];
    }

    /**
     * @return array{0: WorkshopUser, 1: RepairOrder, 2: Estimate, 3: Document}
     */
    private function buildRegenerableEstimate(
        EstimateStatus $estimateStatus = EstimateStatus::Generated,
        RepairOrderStatus $repairOrderStatus = RepairOrderStatus::Estimated,
    ): array {
        [$workshopUser, $repairOrder] = $this->buildRepairOrderWithLine($repairOrderStatus);

        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'version' => 1,
            'status' => $estimateStatus,
            'subtotal_cents' => 9000,
            'tax_cents' => 0,
            'total_cents' => 9000,
            'generated_at' => now()->subDay(),
        ]);

        EstimateLine::factory()->create([
            'estimate_id' => $estimate->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Old snapshot line',
            'quantity' => '1.00',
            'unit_price_cents' => 9000,
            'tax_rate' => '0.00',
            'subtotal_cents' => 9000,
            'tax_cents' => 0,
            'total_cents' => 9000,
            'sort_order' => 1,
        ]);

        $oldDocument = $estimate->documents()->create([
            'workshop_id' => $estimate->repairOrder->workshop_id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => 'documents_local',
            'path' => 'workshops/'.$estimate->repairOrder->workshop_id.'/estimates/'.$estimate->id.'/existing.pdf',
            'filename' => 'existing.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'existing'),
            'generated_at' => now(),
            'created_by_user_id' => null,
        ]);

        return [$workshopUser, $repairOrder->refresh(), $estimate->refresh(), $oldDocument];
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
