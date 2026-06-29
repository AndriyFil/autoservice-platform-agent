<?php

namespace App\Support\Intake;

use App\Enums\MissingIntakeField;

interface MissingNextIntakeFieldRule
{
    public function field(): MissingIntakeField;

    public function matches(IntakeExtractionSnapshot $snapshot): bool;
}
