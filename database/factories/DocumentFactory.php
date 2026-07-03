<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Estimate;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'documentable_type' => Estimate::class,
            'documentable_id' => Estimate::factory(),
            'type' => DocumentType::EstimatePdf,
            'status' => DocumentStatus::Generated,
            'disk' => 'documents',
            'path' => 'workshops/1/estimates/estimate-1.pdf',
            'filename' => 'estimate-1.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 1024,
            'checksum_sha256' => hash('sha256', 'fixture'),
            'generated_at' => now(),
            'created_by_user_id' => null,
        ];
    }
}
