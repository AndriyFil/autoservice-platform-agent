<?php

namespace App\Support\Intake;

class OpenAiIntakeExtractionResultMapper
{
    /**
     * @param array<string, mixed> $data
     */
    public function map(array $data): IntakeExtractionResult
    {
        $vehicle = $data['vehicle'] ?? [];

        if (! is_array($vehicle)) {
            $vehicle = [];
        }

        return new IntakeExtractionResult(
            phone: $data['phone'] ?? null,
            vehicleMake: $vehicle['make'] ?? null,
            vehicleModel: $vehicle['model'] ?? null,
            vehiclePlate: $vehicle['plate'] ?? null,
            preferredTimeText: $data['preferred_time_text'] ?? null,
            problemSummary: $data['problem_summary'] ?? null,
            missingNextField: $data['missing_next_field'] ?? null,
            confidence: $this->confidenceOrNull($data['confidence'] ?? null),
        );
    }

    private function confidenceOrNull(mixed $value): ?float
    {
        if (! is_int($value) && ! is_float($value)) {
            return null;
        }

        return max(0.0, min(1.0, (float) $value));
    }
}
