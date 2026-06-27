<?php

namespace App\Support\Intake;

interface IntakeExtractorInterface
{
    public function extract(string $message): IntakeExtractionResult;
}
