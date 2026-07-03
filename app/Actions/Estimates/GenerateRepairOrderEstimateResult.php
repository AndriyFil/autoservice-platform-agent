<?php

namespace App\Actions\Estimates;

use App\Models\Document;

readonly class GenerateRepairOrderEstimateResult
{
    public function __construct(
        public Document $document,
    ) {}
}
