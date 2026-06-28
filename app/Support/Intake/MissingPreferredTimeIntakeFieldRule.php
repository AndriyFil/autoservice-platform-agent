<?php

namespace App\Support\Intake;

class MissingPreferredTimeIntakeFieldRule implements MissingNextIntakeFieldRule
{
    public function field(): string
    {
        return 'preferred_time';
    }

    public function matches(IntakeExtractionSnapshot $snapshot): bool
    {
        return $snapshot->preferredTimeText === null || $snapshot->preferredTimeText === '';
    }
}
