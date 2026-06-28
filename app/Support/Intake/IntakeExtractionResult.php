<?php

namespace App\Support\Intake;

class IntakeExtractionResult
{
    public function __construct(
        public readonly ?string $phone,
        public readonly ?string $vehicleMake,
        public readonly ?string $vehicleModel,
        public readonly ?string $vehiclePlate,
        public readonly ?string $preferredTimeText,
        public readonly ?string $problemSummary,
        public readonly ?string $missingNextField,
        public readonly ?float $confidence,
    ) {}

    /**
     * @return array{
     *     phone: string|null,
     *     vehicle_make: string|null,
     *     vehicle_model: string|null,
     *     vehicle_plate: string|null,
     *     preferred_time_text: string|null,
     *     problem_summary: string|null,
     *     missing_next_field: string|null,
     *     confidence: float|null
     * }
     */
    public function toArray(): array
    {
        return [
            'phone' => $this->phone,
            'vehicle_make' => $this->vehicleMake,
            'vehicle_model' => $this->vehicleModel,
            'vehicle_plate' => $this->vehiclePlate,
            'preferred_time_text' => $this->preferredTimeText,
            'problem_summary' => $this->problemSummary,
            'missing_next_field' => $this->missingNextField,
            'confidence' => $this->confidence,
        ];
    }
}
