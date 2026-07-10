<?php

namespace Tests\Feature;

use App\Actions\Estimates\GenerateRepairOrderEstimateAction;
use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Domain\RepairOrders\Enums\RepairOrderStatus;
use App\Domain\Workshops\Enums\WorkshopUserRole;
use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GenerateRepairOrderEstimateActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_estimate_pdf_from_repair_order_lines(): void
    {
        Storage::fake('documents_local');

        [$workshopUser, $repairOrder] = $this->buildRepairOrderWithLine();

        $result = app(GenerateRepairOrderEstimateAction::class)->handle($workshopUser, $repairOrder);

        $estimate = Estimate::query()->with('lines')->sole();

        $this->assertSame($estimate->id, $result->document->documentable_id);
        $this->assertSame('Generated estimate line', $estimate->lines->sole()->description);
        $this->assertSame(13000, $estimate->total_cents);
        $this->assertSame(DocumentStatus::Generated, $result->document->status);
        $this->assertSame(DocumentType::EstimatePdf, $result->document->type);
        Storage::disk('documents_local')->assertExists($result->document->path);
    }

    public function test_it_creates_next_estimate_version_without_mutating_previous_pdf_document(): void
    {
        Storage::fake('documents_local');

        [$workshopUser, $repairOrder] = $this->buildRepairOrderWithLine(RepairOrderStatus::Draft);

        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'status' => 'generated',
            'subtotal_cents' => 5000,
            'tax_cents' => 0,
            'total_cents' => 5000,
            'generated_at' => now()->subDay(),
        ]);
        EstimateLine::factory()->create([
            'estimate_id' => $estimate->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Old estimate line',
            'quantity' => '1.00',
            'unit_price_cents' => 5000,
            'tax_rate' => '0.00',
            'subtotal_cents' => 5000,
            'tax_cents' => 0,
            'total_cents' => 5000,
        ]);
        $oldDocument = $estimate->documents()->create([
            'workshop_id' => $repairOrder->workshop_id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => 'documents_local',
            'path' => 'workshops/'.$repairOrder->workshop_id.'/estimates/'.$estimate->id.'/old.pdf',
            'filename' => 'old.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
            'checksum_sha256' => hash('sha256', 'old'),
            'generated_at' => now()->subDay(),
            'created_by_user_id' => null,
        ]);

        $repairOrder->lines()->delete();
        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Second version estimate line',
            'quantity' => '2.00',
            'unit_price_cents' => 15000,
            'tax_rate' => '0.00',
        ]);

        $result = app(GenerateRepairOrderEstimateAction::class)->handle($workshopUser, $repairOrder);

        $estimates = Estimate::query()->with('lines')->orderBy('version')->get();
        $documents = Document::query()->orderBy('id')->get();

        $this->assertSame(DocumentStatus::Generated, $oldDocument->refresh()->status);
        $this->assertCount(2, $estimates);
        $this->assertSame(1, $estimates[0]->version);
        $this->assertSame(2, $estimates[1]->version);
        $this->assertCount(2, $documents);
        $this->assertSame(DocumentStatus::Generated, $result->document->status);
        $this->assertSame($documents->last()->id, $result->document->id);
        $this->assertSame(1, $estimate->refresh()->generatedEstimatePdfDocuments()->count());
        $this->assertSame('Old estimate line', $estimates[0]->lines->sole()->description);
        $this->assertSame('Second version estimate line', $estimates[1]->lines->sole()->description);
        $this->assertSame(5000, $estimates[0]->total_cents);
        $this->assertSame(30000, $estimates[1]->total_cents);
    }

    /**
     * @return array{0: WorkshopUser, 1: RepairOrder}
     */
    private function buildRepairOrderWithLine(RepairOrderStatus $status = RepairOrderStatus::Draft): array
    {
        $workshop = Workshop::factory()->create();
        $user = User::factory()->create();
        $workshopUser = $this->createMembership($user, $workshop);
        $repairOrder = RepairOrder::factory()->forWorkshop($workshop)->create([
            'status' => $status,
        ]);

        RepairOrderLine::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Generated estimate line',
            'quantity' => '1.00',
            'unit_price_cents' => 13000,
            'tax_rate' => '0.00',
        ]);

        return [$workshopUser, $repairOrder->refresh()];
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
