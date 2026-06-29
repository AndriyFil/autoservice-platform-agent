<?php

namespace App\Support\Intake;

use App\Enums\MissingIntakeField;

class MissingVehicleIntakeFieldRule implements MissingNextIntakeFieldRule
{
    public function field(): MissingIntakeField
    {
        return MissingIntakeField::Vehicle;
    }

    public function matches(IntakeExtractionSnapshot $snapshot): bool
    {
        return $snapshot->vehicleMake === null
            && $snapshot->vehicleModel === null
            && $snapshot->vehiclePlate === null;
    }
}
