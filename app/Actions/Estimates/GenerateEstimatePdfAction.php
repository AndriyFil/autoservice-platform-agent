<?php

namespace App\Actions\Estimates;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\EstimateStatus;
use App\Models\Document;
use App\Models\Estimate;
use App\Support\Documents\WorkshopDocumentStorage;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateEstimatePdfAction
{
    public function __construct(
        private readonly WorkshopDocumentStorage $documentStorage,
    ) {}

    public function handle(Estimate $estimate): Document
    {
        $estimate = Estimate::query()
            ->with(['lines', 'repairOrder.customer', 'repairOrder.vehicle', 'repairOrder.workshop'])
            ->whereKey($estimate->id)
            ->firstOrFail();

        $filename = sprintf('estimate-%d-v%d.pdf', $estimate->repair_order_id, $estimate->version);
        $path = sprintf(
            'workshops/%d/estimates/%d/%s-%s',
            $estimate->repairOrder->workshop_id,
            $estimate->id,
            now()->format('YmdHis'),
            $filename,
        );

        $contents = Pdf::loadView('pdf.estimates.show', [
            'estimate' => $estimate,
        ])->output();

        $storedDocument = $this->documentStorage->put($path, $contents);

        $estimate->update([
            'status' => EstimateStatus::Generated,
            'generated_at' => now(),
        ]);

        return $estimate->documents()->create([
            'workshop_id' => $estimate->repairOrder->workshop_id,
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => $storedDocument['disk'],
            'path' => $storedDocument['path'],
            'filename' => $filename,
            'mime_type' => 'application/pdf',
            'size_bytes' => $storedDocument['size_bytes'],
            'checksum_sha256' => $storedDocument['checksum_sha256'],
            'generated_at' => now(),
            'created_by_user_id' => $estimate->created_by_user_id,
        ]);
    }
}
