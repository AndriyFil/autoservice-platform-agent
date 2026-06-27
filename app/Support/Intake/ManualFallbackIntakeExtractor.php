<?php

namespace App\Support\Intake;

use App\Support\PhoneNormalizer;

class ManualFallbackIntakeExtractor implements IntakeExtractorInterface
{
    public function __construct(
        private readonly PhoneNormalizer $phoneNormalizer,
    ) {}

    public function extract(string $message): IntakeExtractionResult
    {
        $phone = $this->extractPhone($message);
        $missingFields = ['vehicle'];

        if ($phone === null) {
            $missingFields[] = 'phone';
        }

        return new IntakeExtractionResult(
            vehicleMake: null,
            vehicleModel: null,
            vehicleYear: null,
            issueText: $message,
            customerSuspectedCause: null,
            preferredTimeText: null,
            phone: $phone,
            missingFields: $missingFields,
            confidence: $phone === null ? 0.0 : 0.2,
        );
    }

    private function extractPhone(string $message): ?string
    {
        preg_match_all('/(?:\+?\d[\d\s().-]{6,}\d)/', $message, $matches);

        foreach ($matches[0] as $match) {
            $normalizedPhone = $this->phoneNormalizer->normalize($match);

            if (strlen($normalizedPhone) >= 7) {
                return $normalizedPhone;
            }
        }

        return null;
    }
}
