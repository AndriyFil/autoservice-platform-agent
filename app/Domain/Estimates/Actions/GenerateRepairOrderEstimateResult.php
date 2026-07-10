<?php

namespace App\Domain\Estimates\Actions;

use App\Models\Document;

readonly class GenerateRepairOrderEstimateResult
{
    public function __construct(
        public Document $document,
    ) {}
}
