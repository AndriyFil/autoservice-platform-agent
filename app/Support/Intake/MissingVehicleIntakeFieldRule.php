<?php

namespace App\Support\Intake;

class MissingVehicleIntakeFieldRule implements MissingNextIntakeFieldRule
{
    public function field(): string
    {
        return 'vehicle';
    }

    public function matches(IntakeExtractionSnapshot $snapshot): bool
    {
        return $snapshot->vehicleMake === null
            && $snapshot->vehicleModel === null
            && $snapshot->vehiclePlate === null;
    }
}
