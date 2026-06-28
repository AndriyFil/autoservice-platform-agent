<?php

namespace App\Support\Intake;

class IntakeExtractionSnapshot
{
    public function __construct(
        public readonly ?string $phone,
        public readonly ?string $vehicleMake,
        public readonly ?string $vehicleModel,
        public readonly ?string $vehiclePlate,
        public readonly ?string $preferredTimeText,
    ) {}
}
