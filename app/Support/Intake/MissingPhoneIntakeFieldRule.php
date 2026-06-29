<?php

namespace App\Support\Intake;

use App\Enums\MissingIntakeField;

class MissingPhoneIntakeFieldRule implements MissingNextIntakeFieldRule
{
    public function field(): MissingIntakeField
    {
        return MissingIntakeField::Phone;
    }

    public function matches(IntakeExtractionSnapshot $snapshot): bool
    {
        return $snapshot->phone === null || $snapshot->phone === '';
    }
}
