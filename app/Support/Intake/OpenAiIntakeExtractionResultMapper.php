<?php

namespace App\Support\Intake;

class OpenAiIntakeExtractionResultMapper
{
    private readonly MissingNextIntakeFieldResolver $missingNextFieldResolver;

    public function __construct(?MissingNextIntakeFieldResolver $missingNextFieldResolver = null)
    {
        $this->missingNextFieldResolver = $missingNextFieldResolver ?? new MissingNextIntakeFieldResolver();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function map(array $data): IntakeExtractionResult
    {
        $vehicle = $data['vehicle'] ?? [];

        if (! is_array($vehicle)) {
            $vehicle = [];
        }

        $phone = $this->stringOrNull($data['phone'] ?? null);
        $vehicleMake = $this->stringOrNull($vehicle['make'] ?? null);
        $vehicleModel = $this->stringOrNull($vehicle['model'] ?? null);
        $vehiclePlate = $this->stringOrNull($vehicle['plate'] ?? null);
        $preferredTimeText = $this->stringOrNull($data['preferred_time_text'] ?? null);

        return new IntakeExtractionResult(
            phone: $phone,
            vehicleMake: $vehicleMake,
            vehicleModel: $vehicleModel,
            vehiclePlate: $vehiclePlate,
            preferredTimeText: $preferredTimeText,
            problemSummary: $this->stringOrNull($data['problem_summary'] ?? null),
            missingNextField: $this->missingNextFieldResolver->resolve(
                phone: $phone,
                vehicleMake: $vehicleMake,
                vehicleModel: $vehicleModel,
                vehiclePlate: $vehiclePlate,
                preferredTimeText: $preferredTimeText,
            )?->value,
            confidence: $this->confidenceOrNull($data['confidence'] ?? null),
        );
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function confidenceOrNull(mixed $value): ?float
    {
        if (! is_int($value) && ! is_float($value)) {
            return null;
        }

        return max(0.0, min(1.0, (float) $value));
    }
}
