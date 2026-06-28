<?php

namespace App\Support\Intake;

class MissingPhoneIntakeFieldRule implements MissingNextIntakeFieldRule
{
    public function field(): string
    {
        return 'phone';
    }

    public function matches(IntakeExtractionSnapshot $snapshot): bool
    {
        return $snapshot->phone === null || $snapshot->phone === '';
    }
}
