<?php

namespace App\Support\Intake;

use App\Support\Phone;

class ManualFallbackIntakeExtractor implements IntakeExtractorInterface
{
    public function __construct(
        private readonly MissingNextIntakeFieldResolver $missingNextFieldResolver,
    ) {}

    public function extract(string $message): IntakeExtractionResult
    {
        $phone = $this->extractPhone($message);

        return new IntakeExtractionResult(
            phone: $phone,
            vehicleMake: null,
            vehicleModel: null,
            vehiclePlate: null,
            preferredTimeText: null,
            problemSummary: $message,
            missingNextField: $this->missingNextFieldResolver->resolve(
                phone: $phone,
                vehicleMake: null,
                vehicleModel: null,
                vehiclePlate: null,
                preferredTimeText: null,
            )?->value,
            confidence: $phone === null ? 0.0 : 0.2,
        );
    }

    private function extractPhone(string $message): ?string
    {
        preg_match_all('/(?:\+?\d[\d\s().-]{6,}\d)/', $message, $matches);

        foreach ($matches[0] as $match) {
            $normalizedPhone = (new Phone($match))->normalize();

            if (strlen($normalizedPhone) >= 7) {
                return $normalizedPhone;
            }
        }

        return null;
    }
}
