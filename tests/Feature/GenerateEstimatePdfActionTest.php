<?php

namespace Tests\Feature;

use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Enums\DocumentType;
use App\Domain\Estimates\Actions\GenerateEstimatePdfAction;
use App\Domain\Estimates\Enums\EstimateStatus;
use App\Domain\RepairOrders\Enums\RepairOrderLineType;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\EstimateLine;
use App\Models\RepairOrder;
use App\Models\RepairOrderLine;
use App\Models\Workshop;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class GenerateEstimatePdfActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_estimate_renders_and_stores_pdf_document(): void
    {
        Storage::fake('documents_local');

        $estimate = $this->createGeneratedEstimateWithLine();

        $document = app(GenerateEstimatePdfAction::class)->handle($estimate);

        $this->assertSame(DocumentStatus::Generated, $document->status);
        $this->assertSame(DocumentType::EstimatePdf, $document->type);
        $this->assertSame(1, Document::query()->count());
        Storage::disk('documents_local')->assertExists($document->path);
    }

    public function test_generation_renders_existing_estimate_lines_not_live_repair_order_lines(): void
    {
        Storage::fake('documents_local');

        $estimate = $this->createGeneratedEstimateWithLine();

        RepairOrderLine::factory()->create([
            'repair_order_id' => $estimate->repair_order_id,
            'description' => 'LIVE repair order line',
            'unit_price_cents' => 99999,
        ]);

        $renderedPdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $renderedPdf->shouldReceive('output')->andReturn('%PDF-fake');

        $captured = null;
        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function (string $view, array $data) use (&$captured, $renderedPdf) {
                $captured = $data;

                return $renderedPdf;
            });

        app(GenerateEstimatePdfAction::class)->handle($estimate);

        $renderedDescriptions = $captured['estimate']->lines->pluck('description');

        $this->assertContains('Prepared estimate line', $renderedDescriptions);
        $this->assertNotContains('LIVE repair order line', $renderedDescriptions);
    }

    private function createGeneratedEstimateWithLine(): Estimate
    {
        $workshop = Workshop::factory()->create();
        $repairOrder = RepairOrder::factory()->forWorkshop($workshop)->create();

        $estimate = Estimate::factory()->create([
            'repair_order_id' => $repairOrder->id,
            'status' => EstimateStatus::Generated,
            'generated_at' => now(),
        ]);

        EstimateLine::factory()->create([
            'estimate_id' => $estimate->id,
            'type' => RepairOrderLineType::Labor,
            'description' => 'Prepared estimate line',
            'unit_price_cents' => 15000,
        ]);

        return $estimate->refresh();
    }
}
