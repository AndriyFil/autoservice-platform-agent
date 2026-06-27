<?php

namespace App\Support\Intake;

class IntakeExtractionResult
{
    /**
     * @param  array<int, string>  $missingFields
     */
    public function __construct(
        public readonly ?string $vehicleMake,
        public readonly ?string $vehicleModel,
        public readonly ?int $vehicleYear,
        public readonly ?string $issueText,
        public readonly ?string $customerSuspectedCause,
        public readonly ?string $preferredTimeText,
        public readonly ?string $phone,
        public readonly array $missingFields,
        public readonly float $confidence,
    ) {}

    /**
     * @return array{
     *     vehicle_make: string|null,
     *     vehicle_model: string|null,
     *     vehicle_year: int|null,
     *     issue_text: string|null,
     *     customer_suspected_cause: string|null,
     *     preferred_time_text: string|null,
     *     phone: string|null,
     *     missing_fields: array<int, string>,
     *     confidence: float
     * }
     */
    public function toArray(): array
    {
        return [
            'vehicle_make' => $this->vehicleMake,
            'vehicle_model' => $this->vehicleModel,
            'vehicle_year' => $this->vehicleYear,
            'issue_text' => $this->issueText,
            'customer_suspected_cause' => $this->customerSuspectedCause,
            'preferred_time_text' => $this->preferredTimeText,
            'phone' => $this->phone,
            'missing_fields' => $this->missingFields,
            'confidence' => $this->confidence,
        ];
    }
}
