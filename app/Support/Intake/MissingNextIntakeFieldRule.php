<?php

namespace App\Support\Intake;

interface MissingNextIntakeFieldRule
{
    public function field(): string;

    public function matches(IntakeExtractionSnapshot $snapshot): bool;
}
