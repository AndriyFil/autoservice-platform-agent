<?php

namespace App\Support\Intake;

use App\Enums\MissingIntakeField;

class MissingPreferredTimeIntakeFieldRule implements MissingNextIntakeFieldRule
{
    public function field(): MissingIntakeField
    {
        return MissingIntakeField::PreferredTime;
    }

    public function matches(IntakeExtractionSnapshot $snapshot): bool
    {
        return $snapshot->preferredTimeText === null || $snapshot->preferredTimeText === '';
    }
}
